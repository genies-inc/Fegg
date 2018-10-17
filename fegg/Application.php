<?php
/**
 * Applicationクラス
 *
 * Webアプリケーションに必要な様々な処理を提供するクラス。
 *
 * 関連ファイル： settings.php
 *
 * @access public
 * @author Genies Inc.
 * @version 1.9.2
 */
class Application
{
    public $config;
    public $languageCode;
    public $page;

    private $_characterCode;
    private $_hidden;
    private $_hiddenForTemplate;
    private $_settings;
    private $_site;
    private $_basicAuthFlag;


    /**
     *  constructor
     */
    function __construct()
    {
        // Fegg設定ファイルを取得
        require(FEGG_DIR . '/settings.php');
        $this->_settings = $settings;

        // メンテナンス中の場合はリダイレクト（開発者を除く）
        if (!$this->_settings['run_mode']) {
            if (!isset($_SERVER['REMOTE_ADDR']) || (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $this->_settings['developer_ip']))) {
                if (isset($this->_settings['maintenance_page_url']) && $this->_settings['maintenance_page_url']) {
                    $this->redirect($this->_settings['maintenance_page_url']);
                } else {
                    echo "This Web page is currently unavailable due to maintenance. Please come back later.";
                    exit;
                }
            }
        }

        // エラー処理設定
        if (!isset($_SERVER['REMOTE_ADDR']) || (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $this->_settings['developer_ip']))) {
            // 本番モード
            define('FEGG_DEVELOPER', '0');
            ini_set( 'display_errors', 0 );
        } else {
            // 開発モード
            define('FEGG_DEVELOPER', '1');
            ini_set('display_errors', 1);
        }
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            set_error_handler(array($this, "errorHandler"), E_ALL ^E_NOTICE ^E_DEPRECATED);
        } else {
            set_error_handler(array($this, "errorHandler"), E_ALL ^E_NOTICE);
        }

        // 文字コード設定
        $this->_characterCode = FEGG_DEFAULT_CHARACTER_CODE;

        // 言語コード設定
        $this->languageCode = $this->getLanguage();
    }


    /**
     * 文字コード変換
     * @param mixed $data
     * @return mixed
     */
    private function _convertCharacterCode($data)
    {
        // 文字コードが変更されている場合のみ処理
        if ($this->_characterCode != FEGG_DEFAULT_CHARACTER_CODE) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $data[$key] = $this->_convertCharacterCode($value);
                    } else {
                        $data[$key] = mb_convert_encoding($value, $this->_characterCode, FEGG_DEFAULT_CHARACTER_CODE);
                    }
                }
            } else {
                $data = mb_convert_encoding($data, $this->_characterCode, FEGG_DEFAULT_CHARACTER_CODE);
            }
        }

        return $data;
    }


    /**
     * リクエストデータ変換
     * @param mixed $data
     * @return mixed
     */
    private function _convertRequestData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                // 自身を再帰呼び出し
                $data[$key] = $this->_convertRequestData($value);
            }
        } else {
            // 文字コード変換
            $this->_convertCharacterCode($data);
        }

        return $data;
    }


    /**
     * テンプレート用にHiddenを編集
     * @param mixed $data
     * @return mixed
     */
    private function _setHiddenForTemplate($data, $currentKey = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {

                // 自身を再帰呼び出し
                $tempKey = $currentKey . "[$key]";
                $data[$key] = $this->_setHiddenForTemplate($value, $tempKey);
            }

        } else if(!empty($currentKey)) {
            $currentKey = preg_replace('/^\[([^\]]+)\]/i', '\1', $currentKey);
            $this->_hiddenForTemplate[$currentKey] = $data;
        }
    }


    /**
     * 多言語対応判定
     * @return Boolean True: 多言語対応 False: 単一言語
     */
    private function _isMultiLanguage()
    {
        if (isset($this->_settings['default_language']) && $this->_settings['default_language']
         && isset($this->_settings['support_language']) && $this->_settings['support_language']) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * アプリケーションサポート言語判定処理
     * configのsupport_languageで設定されている言語コードかどうか判定する
     * @param string $languageCode 言語コード
     * @return boolean サポート言語: True サポート言語でない: False
     */
    private function _isSupportLanguage($languageCode)
    {
        // 言語コードが空白の場合
        if (!$languageCode) { return false; }

        // アプリケーションがサポートする言語コードを取得
        $supportLanguage = isset($this->_settings['support_language']) ? $this->_settings['support_language'] : '';

        // サポート言語判定
        if ($languageCode && is_array($supportLanguage) && in_array($languageCode, $supportLanguage)) {
            return true;
        }else {
            return false;
        }
    }


    /**
     * 名前空間付きクラス名をlib以下のパスとして変換した文字列を返す
     * @param string $classNamespace 名前空間付きクラス名
     * @return string 名前空間をlib以下のパスに置き換えた文字列
     */
    private function _namespaceToPath($classNamespace) {
        $path = ltrim($classNamespace, '\\');
        $libRoot = FEGG_CODE_DIR . DIRECTORY_SEPARATOR . 'lib' .DIRECTORY_SEPARATOR;
        return $libRoot . str_replace('\\', DIRECTORY_SEPARATOR, $path) . '.php';
    }


    /**
     * Basic認証
     * 認証範囲は以下の通り。
     *   0：認証しない
     *   1：すべて認証（displayTemplate で処理。Webアクセス時のみで静的ページを除く。）
     *   2：$this->basicAuth() を記述した部分のみ認証
     *
     * PHPがCGI版の場合「RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]」を.htaccessに追記
     *
     * @param  string $user Basic認証ユーザーの個別指定
     * @param  string $password Basic認証パスワードの個別指定
     */
    function basicAuth($user = '', $password = '')
    {
        // 認証範囲
        $scope = isset($this->_settings['basic_auth_scope']) && $this->_settings['basic_auth_scope'] ? $this->_settings['basic_auth_scope'] : 0;
        if ($scope > 0) {

            // 認証
            $user = $user ? $user : $this->_settings['basic_auth_user'];
            $password = $password ? $password : $this->_settings['basic_auth_password'];

            if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) || !$_SERVER['PHP_AUTH_USER']
             || $_SERVER['PHP_AUTH_USER'] <> $user || $_SERVER['PHP_AUTH_PW'] <> $password)
            {
                // メッセージの設定
                $authMessage = isset($this->_settings['basic_auth_message']) && $this->_settings['basic_auth_message'] ? $this->_settings['basic_auth_message'] : "Enter username and password.";
                $authErrorMessage = isset($this->_settings['basic_auth_error_message']) && $this->_settings['basic_auth_error_message'] ? $this->_settings['basic_auth_message'] : "Authorization Required";

                // 認証前、認証失敗時
                header('HTTP/1.0 401 Unauthorized');
                header('WWW-Authenticate: Basic realm=' . $authMessage);
                header('Content-Type: text/plain; charset=utf-8');
                echo $authErrorMessage;
                exit;
            }
        }
    }


    /**
     * 指定したタイムゾーンの日付に変換
     * @param string $date 変換元日付
     * @param string $fromTimezone 変換元タイムゾーン（Asia/Tokyo形式）
     * @param string $toTimezone 変換先タイムゾーン（Asia/Tokyo形式）
     * @return string 変換後日付
     */
    function convertDatetime($date, $fromTimezone, $toTimezone)
    {
        // 変換元タイムゾーンで日付をtimeに変換
        date_default_timezone_set($fromTimezone);
        $time = strtotime($date);

        // timeから変換先タイムゾーンで日付に変換
        date_default_timezone_set($toTimezone);
        $date = date('Y-m-d H:i:s', $time);

        return $date;
    }




    /**
     * 画面表示
     * @param string $template テンプレートファイル名（拡張子は不要）
     */
    function displayPage($template)
    {
        // Basic認証の処理対象に設定
        $this->_basicAuthFlag = 'true';

        // 画面編集
        $contents = $this->fetchPage($template);

        // クリックジャッキング対策
        header('X-FRAME-OPTIONS: DENY');

        // 画面表示
        echo $contents;
        exit;
    }


    /**
     * 指定されたテンプレートの出力結果を出力
     * @param string $template テンプレートID
     * @param array $assignedValue 表示データ
     * @return テンプレートの実行結果を画面出力
     */
    function displayTemplate($template, $assignedValue = array())
    {
        // カレントディレクトリ指定があり、テンプレートIDの頭が「/」じゃない場合は相対パス
        if( isset( $this->_settings['current_template_dir'] ) && ! empty( $this->_settings['current_template_dir'] ) && substr($template, 0, 1) !== '/' ) {
            $template = $this->_settings['current_template_dir'].$template;
        }
        // 相対パスの指定を削除する
        if (is_numeric(strpos($template, '.'))) {
            $stack = array();
            foreach( explode( '/', $template ) as $path ) {
                if( $path === '..' ) {
                    if( count( $stack ) ) {
                        array_pop( $stack );
                    }
                } else if( $path !== '.' && $path !== '' ) {
                    array_push( $stack, $path );
                }
            }
            $template = implode( '/', $stack );
        }

        // ディレクトリを設定
        $languageDirectory = '';
        if ($this->_isMultiLanguage()) {
            $languageDirectory = '/' . $this->languageCode;
        }

        $template = substr($template, 0, 1) == '/' ? substr($template, 1) : $template;
        $templateFile = $this->_settings['template_dir'] . $languageDirectory . '/' . $template . '.'.$this->_settings['template_ext'];
        $cacheFile = $this->_settings['template_cache_dir'] . '/' . str_replace('/', '@', $languageDirectory . '/' . $template) . '.cache.php';

        // テンプレートファイルが存在しない場合は処理を終了
        if (!file_exists($templateFile)) {
            echo "Can't Read: " . $templateFile;
            return;
        }
        // テンプレートファイルのカレントディレクトリパス
        $currentDir = str_replace( $this->_settings['template_dir'].'/', '', dirname( $templateFile ) ).'/';

        // テンプレートが更新されている場合はキャッシュファイルを作成
        if (!file_exists($cacheFile) || filemtime($cacheFile) < filemtime($templateFile)) {

            // テンプレート取得
            $compiledTemplate = file_get_contents($templateFile);

            // 継承処理
            $pattern = '/ *\{\{ transclude [\'"]([^\'"]+)[\'"] \}\}(.*)\s*/s';
            if (preg_match($pattern, $compiledTemplate, $matches)) {

                // コンポーネントの継承処理
                $parentTemplate = '';
                $parentParts = '';
                if (isset($matches[1]) && $matches[1]) {
                    // 頭が「/」じゃない場合は相対ファイルとして読み込む
                    if( substr($matches[1], 0, 1) == '/' ) {
                        $parentTemplate = substr($matches[1], 1);
                    } else {
                        $parentTemplate = $currentDir . $matches[1];
                    }

                    // 継承元テンプレートに同名のsectionが存在する場合は定義のみ行う
                    if (!file_exists($this->_settings['template_dir']  . $languageDirectory . '/' . $parentTemplate . '.'.$this->_settings['template_ext'])) {
                        echo "Can't extend: " . $this->_settings['template_dir']  . $languageDirectory . '/' . $parentTemplate . '.'.$this->_settings['template_ext'];
                        exit;
                    }
                    $parentTemplate = file_get_contents($this->_settings['template_dir']  . $languageDirectory . '/' . $parentTemplate . '.'.$this->_settings['template_ext']);

                    $tempPattern = array();
                    if (preg_match_all('/ *\{\{\s*section\s+(\w+)\s*\}\}\s*/', $parentTemplate, $parentParts)) {
                        foreach ($parentParts[1] as $key => $value) {
                            $tempPattern['/ *\{\{\s*end\s+section\s+(' . $value . ')\s*\}\}\s*/'] = '<?php }} ?>';
                        }
                        $compiledTemplate = preg_replace(array_keys($tempPattern), array_values($tempPattern), $compiledTemplate);
                    }
                }

                $replacement = '$2<?php $assignedClass[\'app\'] = FEGG_getInstance(); $assignedClass[\'app\']->setCurrentTemplateDirectory("'.$currentDir.'"); $assignedClass[\'app\']->displayTemplate("$1", $assignedValue); ?>';
                $compiledTemplate = preg_replace($pattern, $replacement, $compiledTemplate);
            }

            // 変数修飾子をPHPに変換
            $function = function($matches) {
                $tokens = explode("|", trim($matches[1]));
                $statement = '$' . trim(array_shift($tokens));
                $variable = $statement;
                $htmlSpecialCharsFlag = true;
                $breakLineFlag = false;
                foreach ($tokens as $modifire) {
                    $parameters = explode(":", trim($modifire));
                    $modifire = array_shift($parameters);
                    if ($htmlSpecialCharsFlag && strtolower($modifire) == "noescape") {
                        $htmlSpecialCharsFlag = false;
                    } else if(! $breakLineFlag && strtolower($modifire) == "br") {
                        $breakLineFlag = true;
                    } else if(strtolower($modifire) == "replace") {
                        $statement = "mb_ereg_replace('" . $parameters[0] . "', '" . $parameters[1] . "', " . $statement . ")";
                    } else {
                        $parameter = ""; foreach ($parameters as $value) { $parameter .= "," . $value; }
                        $statement = $modifire . "(" . $statement . $parameter . ")";
                    }
                }
                if ($htmlSpecialCharsFlag && $breakLineFlag) {
                    return "<?php if (isset($variable) && !is_array($variable)) { echo nl2br( htmlSpecialChars($statement, ENT_QUOTES, '' . FEGG_DEFAULT_CHARACTER_CODE . '') ); } ?>";
                } else if($htmlSpecialCharsFlag) {
                    return "<?php if (isset($variable) && !is_array($variable)) { echo htmlSpecialChars($statement, ENT_QUOTES, '' . FEGG_DEFAULT_CHARACTER_CODE . ''); } ?>";
                } else if($breakLineFlag) {
                    return "<?php if (isset($variable) && !is_array($variable)) { echo nl2br($statement); } ?>";
                } else {
                    return "<?php if (isset($variable) && !is_array($variable)) { echo $statement; } ?>";
                }
            };
            $compiledTemplate = preg_replace_callback('/\{\{\s*\$(.+)\s*\}\}\s*/U', $function, $compiledTemplate);

            // 基本命令をPHPに変換
            $pattern = array(
                '/ *\{\-\{/i' => '{{',
                '/\s*\{\--\{/i' => '{{',
                '/\}\-\} */i' => '}}',
                '/\}\--\}\s*/i' => '}}',
                '/\{\{\s*section\s+(\w+)\s*\}\}/i' => '<?php if (!function_exists("section_$1")) { function section_$1($assignedValue) { ?>',
                '/\{\{\s*end section (\w+)\s*\}\}/i' => '<?php }} section_$1($assignedValue); ?>',
                '/\{\{\s*head\s*\}\}/i' => '<?php if (isset($head)) { $assignedClass[\'app\'] = FEGG_getInstance(); foreach($head as $key => $value) { $assignedClass[\'app\']->setCurrentTemplateDirectory($value[\'dir\']); $assignedClass[\'app\']->displayTemplate($value[\'file\'], $assignedValue); } } ?>',
                '/\{\{\s*include\s+head\s+(\'*[^\s]+\'*)\s*\}\}/i' => '<?php if (isset($head)) { array_unshift($head, array( \'file\'=> $1, \'dir\'=>\''.$currentDir.'\' )); } else { $head[] = array( \'file\'=> $1, \'dir\'=>\''.$currentDir.'\' ); } ?>',
                '/\{\{\s*include\s+([^\};]+)\s*\}\}/i' => '<?php $assignedClass[\'app\'] = FEGG_getInstance(); $assignedClass[\'app\']->setCurrentTemplateDirectory(\''.$currentDir.'\'); $assignedClass[\'app\']->displayTemplate($1, $assignedValue); ?>',
                '/\{\{\s*include\s+html\s+(\'*[^\s]+\'*)\s*\}\}/i' => '<?php include(FEGG_HTML_DIR . $1); ?>',
                '/\{\{\s*assign\s+(\$[\w\.\[\]\$]+)\s*=\s*(\s*[^\}]+)\s*\}\}/i' => '<?php $1 = $2 ?>',
                '/\{\{\s*if\s+(\s*\$[\$\w\.\[\]\_\']+)\s*\}\}/i' => '<?php if (isset($1) && $1) { ?>',
                '/\{\{\s*if\s+([^\{]+)\s*\}\}/i' => '<?php if ($1) { ?>',
                '/\{\{\s*else\s*if\s*([^\{]+)\s*\}\}/i' => '<?php } else if ($1) { ?>',
                '/\{\{\s*else\s*\}\}/' => '<?php } else { ?>',
                '/\{\{\s*loop\s+\$(\w+)\s*=\s*([$]*[\w\.]+)\s*to\s*([$]*[\w\.]+)\s*\}\}/i' => '<?php for ($$1 = $2; $$1 <= $3; $$1++) { ?>',
                '/\{\{\s*end\s*\}\}/i' => '<?php } ?>',
                '/\{\{\s*foreach\s+\$((?!.*foreach).+)\s+as\s+\$(\w+)\s*=>\s*\$(\w+)\s*\}\}/i' => '<?php $foreachIndex = 0; $$1 = isset($$1) ? (array)$$1 : array(); foreach ($$1 as $$2 => $$3) { ?>',
                '/\{\{\s*end foreach\s*\}\}/i' => '<?php $foreachIndex++; } ?>',
                '/\{\{\s*hidden\s*\}\}/i' => '<?php if (isset($hiddenForTemplate)) { foreach ($hiddenForTemplate as $fegg_hiddens_key => $fegg_hiddens_value) { echo \'<input type="hidden" name="\' . $fegg_hiddens_key . \'" value="\' . $fegg_hiddens_value . \'">\'; }} ?>',
                '/\{\{\s*base\s*\}\}/i' => '<?php echo FEGG_REWRITEBASE; ?>',
                '/\{\{\*.*\*\}\}/i' => '',
            );

            $compiledTemplate = preg_replace(array_keys($pattern), array_values($pattern), $compiledTemplate);

            // call をPHPに変換
            $pattern = array();
            if (preg_match_all('/\{\{\s*call\s+\'[\/]*([\w\/]+)\'\s*\}\}/i', $compiledTemplate, $matches)) {
                $tempPath = '';
                $nameSpace = '';
                $fileName = '';
                $methodName = '';
                $uriSegments = explode('/', $matches[1][0]);
                foreach ($uriSegments as $key => $value) {
                    if ($tempPath) {
                        $tempPath .= '/';
                        $nameSpace .= '_';
                    }
                    // 同一階層に同一のフォルダ名とファイル名が存在する場合はファイルを優先する
                    if (file_exists(FEGG_CODE_DIR . '/application/' . $tempPath . ucwords($value) . '.php')) {
                        $fileName = ucwords($value);
                        $methodName = isset($uriSegments[$key + 1]) ? $uriSegments[$key + 1] : '';
                        $parameter = array_slice($uriSegments, $key + 2);
                        break;
                    }
                    $tempPath .= $value;
                    $nameSpace .= ucwords($value);
                }
                if (file_exists(FEGG_CODE_DIR . '/application/' . $tempPath . $fileName . '.php')) {
                    $statement = '';
                    $statement .= '<?php require_once FEGG_CODE_DIR . \'/application/' . $tempPath . $fileName . '.php\'; $assignedClass[\'' . $nameSpace . $fileName . '\'] = new ' . $nameSpace . $fileName . '(); $assignedClass[\'' . $nameSpace . $fileName . '\']->' . $methodName . '(); ?>';
                    $pattern[$matches[0][0]] = $statement;
                }
            }

            $compiledTemplate = str_replace(array_keys($pattern), array_values($pattern), $compiledTemplate);

            // checked, selected をPHPに変換
            $pattern = array();
            if (preg_match_all('/\{\{\s*(checked|selected)\s+(key\s*=\s*[\(\)\$\w\.\[\]\_\'\s\"\-]+\s+value\s*=\s*[\$\w\.\[\]\_\'\s\"\-]+)(\s+typecheck=[\w]+)*\s*\}\}/i', $compiledTemplate, $matches)) {
                foreach ($matches[2] as $key => $paramater) {
                    $parameter = preg_replace('/\s+/', ' ', trim($paramater));
                    $parameter = preg_replace('/\s+value=/', '|value=', trim($paramater));
                    $elements = explode("|", $parameter);
                    $tempElements = array();
                    foreach ($elements as $element) {
                        list($id, $value) = explode("=", $element);
                        $tempElements[trim($id)] = trim($value);
                    }
                    $elements = $tempElements;

                    $typecheck = isset($matches[3]) && $matches[3][0] != '' ? ', true' : '';

                    $statement = '';
                    $statement .= '<?php if (';
                    if (!is_numeric($elements['key']) && !is_string($elements['key'])) {
                        $statement .= 'isset(' . $elements['key'] . ') && ';
                    }
                    $operator = $typecheck ? ' === ' : ' == ';
                    $statement .= 'isset(' . $elements['value'] . ') && (' . $elements['value'] . $operator . $elements['key'] . ' || (is_array(' . $elements['value'] . ') && in_array(' . $elements['key'] . ', ' . $elements['value'] . $typecheck . ')))) { echo \' ' . $matches[1][$key] . '\'; } ?>';
                    $pattern[$matches[0][$key]] = $statement;
                }
            }

            $compiledTemplate = str_replace(array_keys($pattern), array_values($pattern), $compiledTemplate);

            // options をPHPに変換
            $pattern = array();
            if (preg_match_all('/\{\{\s*options\s+([^\}]+)\s*\}\}/i', $compiledTemplate, $matches)) {
                foreach ($matches[1] as $key => $paramater) {
                    $elements = explode(" ", trim($paramater));
                    $tempElements = array();
                    foreach ($elements as $element) { ;
                        list($id, $value) = explode("=", $element);
                        $tempElements[trim($id)] = trim($value);
                    }
                    $elements = $tempElements;

                    $statement = '';
                    $statement .= '<?php if (' . $elements['source'] . ') { ?>';
                    $statement .= '<?php foreach (' . $elements['source'] . ' as $templateValue[\'key\'] => $templateValue[\'value\']) { ?>';
                    $statement .= '<?php echo \'<option value=\\\'\' . $templateValue[\'key\'] . \'\\\'\'; ';
                    if (isset($elements['selected'])) {
                        $statement .= 'if (isset(' . $elements['selected'] . ') && (string) $templateValue[\'key\'] === (string) ' . $elements['selected'] . ') { echo \' selected\'; } ';
                    }
                    $statement .= 'echo \'>\' . $templateValue[\'value\'] . \'</option>\'; ?>';
                    $statement .= '<?php } ?>';
                    $statement .= '<?php } ?>';

                    $pattern[$matches[0][$key]] = $statement;
                }
            }

            $compiledTemplate = str_replace(array_keys($pattern), array_values($pattern), $compiledTemplate);

            // code をPHPに変換
            if (preg_match_all('/\{\{\s*code\s+([^\{\}]+)\s*\}\}/i', $compiledTemplate, $matches)) {
                foreach ($matches[1] as $key => $paramater) {
                    $compiledTemplate = str_replace($matches[0][$key], '<?php ' . $paramater . ' ?>', $compiledTemplate);
                }
            }

            // 変数を$assignedValueの要素として変換
            $function = function($matches) {
                if (trim($matches[1]) == '$assignedValue' || trim($matches[1]) == '$assignedClass' || trim($matches[1]) == '$assignedHead') { return trim($matches[1]); }
                $variables = explode(".", trim($matches[1]));
                $element = "";
                foreach ($variables as $variable) {
                    $variable = str_replace('$', '', $variable);
                    $element .= "['" . "$variable" . "']";
                }
                return '$assignedValue' . $element;
            };

            $pattern = array();
            preg_match_all('/\<\?php\s+((?!\?\>).)+\s+\?\>/i', $compiledTemplate, $matches);

            foreach ($matches[0] as $key => $value) {
                $pattern[$value] =  preg_replace_callback('/(\$[\$\w\.\_\']+)/i', $function, $value);
            }

            $compiledTemplate = str_replace(array_keys($pattern), array_values($pattern), $compiledTemplate);

            // $variable[$id].id 形式への対応（５次元まで対応）
            $compiledTemplate = preg_replace('/(\[\'*[^\]]+\'*\])\.(\w+)/', '\1[\'\2\']', $compiledTemplate);
            for($i = 0; $i < 5; $i++) {
                $compiledTemplate = preg_replace('/(\[*\])\.(\w+)/', '\1[\'\2\']', $compiledTemplate);
            }

            // ？＞で終わる場合の改行対応
            $compiledTemplate = preg_replace('/\?>(?:\r\n|\n)/m', '?> ' . "\n", $compiledTemplate);

            // キャッシュファイル生成
            file_put_contents($cacheFile, trim($compiledTemplate), LOCK_EX);
            chmod($cacheFile, 0666);
        }

        // Basic認証処理可能（fetchTempalte 経由ではない）で「すべて認証」に設定されているかを判定
        if (($this->_basicAuthFlag == 'true' || $this->_basicAuthFlag == '') && $this->_settings['basic_auth_scope'] == 1) {
            $this->basicAuth();
        }

        require($cacheFile);
    }


    /**
     * アプリケーション独自の "空" 判定
     * '', array(), NULL, false を "空" として判定
     * @param  mixed 判定する変数
     * @return boolean 空なら ture そうでなければ false
     */
    function empty($var)
    {
        if ((is_array($var) && count($var) == 0)
         || ($var === "")
         || ($var === NULL)
         || ($var === false)) {

            return true;

        } else {
            return false;
        }
    }


    /**
     * エラー時の例外発生
     * @param int $errorNo
     * @param string $errorMessage
     * @param string $errorFile
     * @param int $errorLine
     */
    function errorHandler($errorNo, $errorMessage, $errorFile, $errorLine)
    {
        // 開発モードでは詳細を表示してから例外を発生させる
        if (FEGG_DEVELOPER) {
            $error = '';
            $error .= "Error File: $errorFile<br/>";
            $error .= "Error Line: $errorLine<br/>";
            $error .= "Error Message: <font color='red'>$errorMessage</font><br/>";

            // エラー対象ファイルの該当行の表示
            if(file_exists($errorFile)) {
                $file = file_get_contents($errorFile);
                $line = explode("\n", $file);
                $error .= '<p></p>';
                for ($i = $errorLine - 10; $i <= $errorLine + 10; $i++) {
                    if ($i > 0 && isset($line[$i - 1])) {
                        if ($i == $errorLine) { $error .= '<font color="red">'; }
                        $error .= $i . ': ' . htmlspecialchars($line[$i - 1]) . '<br/>';
                        if ($i == $errorLine) { $error .= '</font>'; }
                    }
                }
            }
            echo '<pre>' . $error . '</pre>';
        }

        // 例外発生
        throw new ErrorException($errorMessage, 0, $errorNo, $errorFile, $errorLine);
    }


    /**
     * 表示用の変数設定と画面編集
     * @param string $template テンプレートファイル名（.tplは不要）
     */
    function fetchPage($template)
    {
        // テンプレートに渡すデータを設定
        $assign = array();

        // ユーザーが定義した定数を設定
        foreach (get_defined_constants(true) as $key => $value) {
            if ($key == 'user') {
                $assign['define'] = $value;
            }
        }

        // クラス名
        $assign['className'] = get_class($this);

        // コンフィグ
        $assign['config'] = $this->config;

        // 文字コード
        $assign['languageCode'] = $this->languageCode;

        // サイト情報
        $assign['site'] = $this->_site;
        $assign['page'] = $this->page;

        // リクエストデータ
        $assign['in'] = $this->in();

        // Hidden
        $this->_setHiddenForTemplate($this->_hidden);
        $assign['hiddenForTemplate'] = $this->_hiddenForTemplate;

        // セッション
        $this->setSession('session_id', session_id());
        $this->setSession('session_name', session_name());
        $assign['session'] = $this->getSession();

        // Cookie
        $assign['cookie'] = $this->getCookie();

        // テンプレート実行
        $contents = $this->fetchTemplate($template, $assign);

        // 文字コード変換
        $contents = $this->_convertCharacterCode($contents);

        return $contents;
    }


    /**
     * 指定されたテンプレートの出力結果を文字列として返す
     * @param string $templateFile テンプレートファイル
     * @param array $assignedValue 表示データ
     * @return string テンプレートの出力結果
     */
    function fetchTemplate($templateFile, $assignedValue = array())
    {
        // 直接コールされている場合はBasic認証の対象外に
        $this->_basicAuthFlag = $this->_basicAuthFlag != '' ? $this->_basicAuthFlag : 'false';

        ob_start();
        $this->displayTemplate($templateFile, $assignedValue);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }


    /**
     * クラスを読み込みインスタンスを返す
     * クラスの読み込み方はパス形式と名前空間形式がある
     *     パス形式     : 読み込むクラスが名前空間を使用していない場合
     *     名前空間形式 : 読み込むクラスが名前空間を使用している場合
     * @param string $file ファイル
     * @param array $parameter
     * @return mixed 正常時：クラスインスタンス 異常時：null
     */
    function getClass()
    {
        // 引数取得
        $parameters = func_get_args();

        // ファイル名、もしくは名前空間を含むクラス名
        $file = array_shift($parameters);

        // XXX : PHP 5.3以降
        // これによってインスタンス化に失敗したクラスの名前空間をlib以下のパスに置き換えファイルを探す
        // つまりgetClass($className)された$classNameクラスが別のクラスをrequire無しで呼び出せる
        spl_autoload_register(function ($class) {
            require_once $this->_namespaceToPath($class);
        });

        // 名前空間形式、もしくはlib直下はautoloadする
        if (strpos($file, DIRECTORY_SEPARATOR) === false) {
            // 異常時(名前空間を含むクラス名と一致するパスにファイルが存在しない場合)
            if(!is_readable($this->_namespaceToPath($file))){
                return null;
            }

            $reflect  = new ReflectionClass($file);
            $instance = $reflect->newInstanceArgs($parameters);
            return $instance;
        }

        // ここからパス形式の場合の処理
        $segments = explode('/', $file);
        $tempPath = '';
        $fileName = '';

        foreach ($segments as $key => $value) {

            // 同一階層に同一のフォルダ名とファイル名が存在する場合はファイルを優先する
            if (file_exists(FEGG_CODE_DIR . '/lib/' . $tempPath . ucwords($value) . '.php')) {
                $fileName = ucwords($value);
                break;
            }
            $tempPath .= $value . '/';
        }

        if ($fileName) {
            require_once(FEGG_CODE_DIR . "/lib/$file.php");
            $className = $fileName;
            // 名前空間を指定しているのにパス形式を指定するとこの時点で$classNameは名前空間なしのものになっている
            // 上のrequire_onceで読み込めているのに名前空間なしの別のクラスとして読み込もうとして失敗しautoloadが呼ばれてしまう
            $reflect  = new ReflectionClass($className);
            $instance = $reflect->newInstanceArgs($parameters);
            return $instance;
        } else {
            return null;
        }
    }


    /**
     * Cookie取得
     * @param string $name
     * @return string 対象のCookie値
     */
    function getCookie($name = '')
    {
        if ($name) {
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        } else {
            return $_COOKIE;
        }
    }


    /**
     * 指定タイムゾーンの日時取得
     * @param string $format 日付フォーマット
     * @param string $timeZone タイムゾーン（Asia/Tokyo形式、省略時はFEGG_DEFAULT_TIMEZONEを使用）
     * @return string 指定したタイムゾーンの日時（指定フォーマット、指定無しは y-m-d H:i:s 形式）
     */
    function getDatetime($format = 'Y-m-d H:i:s', $timeZone = '')
    {
        if ($timeZone) {
            date_default_timezone_set($timeZone);
        } else {
            date_default_timezone_set(FEGG_DEFAULT_TIMEZONE);
        }
        return date($format);
    }


    /**
     * インスタンス取得
     * @return Application このクラスのインスタンス
     */
    function &getInstance()
    {
        return $this;
    }


    /**
     * 実行環境から言語コードを取得
     * @return string 言語コード
     */
    function getLanguage()
    {

        // URLで指定された言語コード
        $languageCode = isset($_GET['lang']) ? $_GET['lang'] : '';
        if ($this->_isSupportLanguage($languageCode)){
            return $languageCode;
        }

        // Cookieで指定された言語コード
        $languageCode = isset($_COOKIE['FEGG_language_code']) ? $_COOKIE['FEGG_language_code'] : '';
        if ($this->_isSupportLanguage($languageCode)){
            return $languageCode;
        }

        // サブドメインで指定された言語コード
        if (isset($_SERVER['SERVER_NAME'])) {
            $languageCode = preg_replace('/^([\w\-]+)\..+$/', "\1", $_SERVER['SERVER_NAME']);
            if ($this->_isSupportLanguage($languageCode)){
                return $languageCode;
            }
        }

        // ブラウザで指定された言語コード
        $languageCode = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

        if ($this->_isSupportLanguage($languageCode)){
            return $languageCode;
        } else {
            // 取得値がサポート言語ではない場合は先頭２文字で再判定
            if ($languageCode && $this->_isSupportLanguage(substr($languageCode, 0, 2))) {
                return substr($languageCode, 0, 2);
            }
        }

        // 実行環境から言語コードが取得できない場合はデフォルト値を返す
        return isset($this->_settings['default_language']) ? $this->_settings['default_language'] : '';
    }


    /**
     * セッション値取得
     * @param String $name
     */
    function getSession($name = '')
    {
        // セッションを開始
        if (!session_id()) {
            session_start();
        }

        if ($name) {
            return isset($_SESSION[$name]) ? $_SESSION[$name] : '';
        } else {
            return $_SESSION;
        }
    }


    /**
     * アプリケーションの設定値取得
     * @param string $name 設定名
     * @return string 設定値
     */
    function getSetting($name)
    {
        return isset($this->_settings[$name]) ? $this->_settings[$name] : '';
    }


    /**
     * リダイレクト
     * @param string $url リダイレクト先URL
     */
    function redirect($url)
    {
        // URLが指定されている場合は指定アドレスにリダイレクト
        if (preg_match( '/^http[s]*:\/\//', $url))  {
            header('Location: ' . $url);
            exit();
        }

        // アプリケーション内のリンクの場合
        if (!preg_match('/^\/.*/', $url))  {
            $url = '/' . $url;
        }
        $url = FEGG_CURRENT_URL . $url;
        header('Location: ' . $url);

        exit();
    }


    /**
     * セッションIDの再発行
     */
    function regeneratSessionId()
    {
        // セッションを開始
        if (!session_id()) {
            session_start();
        }

        // $_SESSIONを利用する場合のセキュリティ対策
        session_regenerate_id(TRUE);
    }


    /**
     * コンフィグファイル読み込み
     * @param string $name コンフィグファイル名（.phpは不要）
     * @param string $languageCode
     * @return コンフィグ（配列）
     */
    function loadConfig($name, $languageCode = '')
    {
        // 既に読み込み済みの場合はその値を返す
        if (isset($this->config[$name])) { return $this->config[$name]; }

        $configFile = "$name.php";
        $languageCode = $languageCode ? $languageCode : $this->languageCode;

        // グローバルコンフィグ
        if ($this->_settings['global_config_dir'] && file_exists($this->_settings['global_config_dir'] . $configFile)) {
            require($this->_settings['global_config_dir'] . $configFile);
        }

        // コンフィグ
        if (file_exists(FEGG_CODE_DIR . "/config/$configFile")) {
            require(FEGG_CODE_DIR . "/config/$configFile");
        }

        // 言語別コンフィグ
        if ($this->_isMultiLanguage()) {
            if (file_exists(FEGG_CODE_DIR . "/config/$languageCode/$configFile")) {
                require(FEGG_CODE_DIR . "/config/$languageCode/$configFile");
            }
        }

        // 読み込み完了確認
        if (isset($$name)) {
            $this->config[$name] = $$name;
            return $this->config[$name];
        } else {
            echo "Can't Read Config File: $name";
            exit;
        }
    }


    /**
     * リクエストデータ取得
     * @param string $name 取得対象のデータ名。省略時は全て取得。
     * @param type $method リクエストメソッド(POST/GET)。省略時は全て取得。
     * @return mixed 取得結果が単一: 取得値（string） / 取得結果が配列 取得値（array）
     */
    function in($name = '', $method = '')
    {
        // 定数との比較判定のため大文字変換
        $method = strtoupper($method);

        // リクエストデータ取得
        $requestData = [];
        if ($name) {

            // 対象のデータを取得
            if (!$method || $method == 'GET') {
                if (isset($_GET[$name]) && $_GET[$name] != "") {
                    $requestData = $_GET[$name];
                }
            }
            if (!$method || $method == 'POST') {
                if (isset($_POST[$name]) && $_POST[$name] != "") {
                    $requestData = $_POST[$name];
                }
            }
        } else {

            // 全データを取得
            if (!$method || $method == 'GET') {
                foreach ($_GET as $key => $value) { $requestData[$key] = $value; }
            }
            if (!$method || $method == 'POST') {
                foreach ($_POST as $key => $value) { $requestData[$key] = $value; }
            }
        }

        // 空の場合は空白を返す（ [] ではなく下位互換のため "" を返す）
        $requestData = !$this->empty($requestData) ? $requestData : "";

        // 文字コード、シングル・ダブルクォートを変換
        return $this->_convertRequestData($requestData);
    }


    /**
     * メール送信
     * テキスト、HTML、ファイル添付に対応したメール送信
     * オプションに指定した連想配列のキーで以下の設定が可能
     * 　'from_name' : 送信者名
     * 　'to_name' : 宛先者名
     * 　'html' : trueで本文をHTMLとして送信
     * 　'file' : 連想配列で指定（file => 'ファイルパス', 'name' => 'ファイル名'）
     * 　　例）$options['file'] => array(0 => array('file' => 'path-to-file', 'name' => 'an attached file'), 1 => array(...));
     *
     * @param  string $to 送信先メールアドレス
     * @param  string $subject タイトル
     * @param  string $message 本文 / HTML
     * @param  string $from 送信元メールアドレス
     * @param  array $options オプション
     * @return int mail()関数の返値
     */
    function mail($to, $subject, $message, $from, $options = array())
    {
        $boundary = 'boundary_' . md5(rand());
        $bounceto = isset($options['bounceto']) ? $options['bounceto'] : $from;

        // mimeエンコード用無名関数
        $mime = function($text, $length = 99999) {
            $index = 0;
            $encoded = '';
            while ($index * $length <= mb_strlen($text)) {
                $encoded .= $encoded ? "\n" : '';
                $encoded .= "=?ISO-2022-JP?B?" . base64_encode(mb_convert_encoding(mb_substr($text, $index * $length, $length), 'ISO-2022-JP', FEGG_DEFAULT_CHARACTER_CODE)) . "?=";
                $index = $index + 1;
            }
            return $encoded;
        };

        // 宛先
        $to = isset($options['to_name']) ? $mime($options['to_name']) . " <{$to}>" : $to;
        $from = isset($options['from_name']) ? $mime($options['from_name']) . " <{$from}>" : $from;

        // ヘッダー
        $header = '';
        $header .= "From: {$from}\n";
        $header .= "MIME-Version: 1.0\n";
        $header .= "Content-Type: Multipart/Mixed; boundary=\"{$boundary}\"\n";
        $header .= "Reply-To: {$bounceto}\n";

        // タイトル
        $subject = $mime($subject, 24);

        // 本文
        $body = '';
        $body .= "--{$boundary}\n";
        if (!isset($options['html']) || $options['html'] != true) {
            // テキスト
            $body .= "Content-Type: text/plain; charset=ISO-2022-JP; Content-Transfer-Encoding: 7bit\n\n";
            $body .= mb_convert_encoding($message, 'ISO-2022-JP', FEGG_DEFAULT_CHARACTER_CODE) . "\n\n";
        } else {
            // HTML
            $body .= "Content-Type: text/html; charset=ISO-2022-JP; Content-Transfer-Encoding: quoted-printable\n\n";
            $body .= mb_convert_encoding(quoted_printable_decode($message), FEGG_DEFAULT_CHARACTER_CODE, "ISO-2022-JP") . "\n\n";
        }

        // 添付ファイル
        if (isset($options['file']) && is_array($options['file'])) {
            foreach ($options['file'] as $key => $value) {
                if (file_exists($value['file'])) {
                    $body .= "--" . $boundary . "\n";
                    $name = $mime(isset($value['name']) ? $value['name'] : basename($value['file']));
                    $body .= "Content-Type: application/octet-stream ; name=\"{$name}\"\n";
                    $body .= "Content-Disposition: attachment; filename=\"{$name}\"\n";
                    $body .= "Content-Transfer-Encoding: base64\n\n";
                    $body .= chunk_split(base64_encode(file_get_contents($value['file'])))."\n";
                }
            }
        }

        // メール送信
        return mail($to, $subject, $body, $header);
    }


    /**
     * 文字コード設定
     * @param string $characterCode 文字コード
     */
    function setCharacterCode($characterCode)
    {
        $this->_characterCode = $characterCode;
    }


    /**
     * Cookieを設定
     * @param string $name Cookie値名称
     * @param string $value Cookie値
     * @parame string $expire 有効期限（秒で指定、0で現在時刻）
     * @param string $path パス
     */
    function setCookie($name, $value, $expire = '', $path = '/')
    {
        // 有効期限
        $expire = $expire > 0 ? time() + $expire : time() + 604800;

        // Cookieに書き出し
        if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
            setcookie($name, $value, $expire, $path, '', false, false);
        } else {
            setcookie($name, $value, $expire, $path, '', false);
        }
    }


    /**
     * テンプレートのカレントディレクトリを設定
     * @param string $dir カレントディレクトリ
     */
    function setCurrentTemplateDirectory( $dir )
    {
        $this->_settings['current_template_dir'] = $dir;
    }


    /**
     * hiddenにデータを設定
     * @param mixed $name hidden名、もしくは {'key' => value} 型の配列
     * @param string $value 設定する値
     */
    function setHidden($name, $value = "")
    {
        if (! is_array($name)) {
            $this->_hidden[$name] = $value;
        } else {
            foreach ($name as $key => $value) {
                $this->_hidden[$key] = $value;
            }
        }
    }


    /**
     * HTML Headerを設定
     * @param string $header ヘッダー
     */
    function setHtmlHeader($header)
    {
        header($header);
    }


    /**
     * 言語コード設定
     * @param string $languageCode
     */
    function setLanguage($languageCode)
    {
        // 言語コードが指定されていて、サポート言語であることを確認
        if ($languageCode && $this->_isSupportLanguage($languageCode)){

            // 言語コードをCookieに保存
            $this->setCookie('FEGG_language_code', $languageCode);

            // 言語コード設定
            $this->languageCode = $languageCode;
        }
    }


    /**
     * セッション値設定
     * @param String $name
     * @param String $value
     */
    function setSession($name, $value)
    {
        // セッションを開始
        if (!session_id()) {
            session_start();
        }
        $_SESSION[$name] = $value;
    }


    /**
     * サイト情報設定
     * @param string $id ID
     * @param string $value 設定値
     */
    function setSiteinfo($id, $value)
    {
        $this->_site[$id] = $value;
    }


    /**
     * リロード対策用ワンタイムチケット発行
     * @param string $name チケット名
     */
    function setTicket($name)
    {
        $ticketName = 'ticket_' . $name;
        $ticket = md5(uniqid() . mt_rand());
        $this->setSession($ticketName, $ticket);
        $this->setHidden($ticketName, htmlspecialchars($ticket, ENT_QUOTES, FEGG_DEFAULT_CHARACTER_CODE));
    }


    /**
     * クッキーを削除
     * @param string $name
     */
    function unsetCookie($name)
    {
        $this->setCookie($name, '', time() - 86500, '/');
    }


    /**
     * hiddenのデータを削除（テンプレートタグ {{hidden}} で出力される）
     * @param string $name
     */
    function unsetHidden($name)
    {
        if ($name) { unset($this->_hidden[$name]); }
    }


    function unsetSession($name = '')
    {
        // セッションを開始
        if (!session_id()) {
            session_start();
        }

        if ($name) {
            unset($_SESSION[$name]);
        } else {
          $_SESSION = array();
          session_destroy();
        }
    }


    /**
     * ワンタイムチケット使用
     * @param string $name
     * @return boolean チケット有効： true / チケット無効： false
     */
    function useTicket($name)
    {
        $ticketName = 'ticket_' . $name;
        $ticketId = $this->in($ticketName, 'POST');

        if (!$ticketId || $this->getSession($ticketName) == '') {
            return false;
        }

        // ワンタイムチケット認証
        if ($ticketId && $ticketId === $this->getSession($ticketName)){
            $this->unsetSession($ticketName);
            return true;
        } else {
            return false;
        }
    }
}


/**
 * アプリケーションの致命的エラー処理
 */
function shutdownHandler()
{
    $error = error_get_last();
    if (defined('FEGG_DEVELOPER') && FEGG_DEVELOPER && $error) {
        echo "<p>Debug Information (Developer Only) by Application::shutdownHandler()</p>";
        echo "Error File: " . $error['file'] . "<br/>";
        echo "Error Line: <a href='#target'>" . $error['line'] . "</a><br/>";
        echo "Error Message: <font color='red'>" . $error['message'] . "</font><br/>";
        if (file_exists($error['file'])) {
            $source = explode("\n", htmlspecialchars(file_get_contents($error['file'])));
            echo '<pre>';
            foreach ($source as $key => $value) {
                if ($key + 1 == $error['line']) {
                    echo '<a name="target" /><font color=red>' . ($key + 1) . ": $value</font><br/>";
                } else {
                    echo ($key + 1) . ": $value<br/>";
                }
            }
            echo '</pre>';
        }
    }
}
register_shutdown_function('shutdownHandler');
