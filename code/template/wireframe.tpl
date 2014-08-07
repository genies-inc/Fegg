{{ transclude '/siteframe' }}
{{ section body }}
        <style type="text/css">
            body {
                padding-top: 70px;
                background-color: #f1f2f6;
            }
            .help-block {
                margin-bottom: 5px;
            }
            .navbar-inverse {
                background-color: #FFF000;
            }
            .navbar-inverse .navbar-nav>li>a,.navbar-inverse .navbar-text{
                    color:#000000
            }
            .navbar .brand {
              display: block;
              float: left;
              padding: 5px 0px 5px;
              margin-left: -15px;
              font-size: 20px;
              font-weight: 200;
              color: #777777;
              text-shadow: 0 1px 0 #ffffff;
            }
            .navbar .brand:hover {
              text-decoration: none;
            }            
        </style>
        
        <div class="container">

            <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
              <div class="container">
                <div class="navbar-header">
                   <a class="brand" href="/"><img src="/images/logo.gif"></a>
                  <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                  </button>
                  <a class="navbar-brand" href="#"></a>
                </div>
                <div class="collapse navbar-collapse">
                  <ul class="nav navbar-nav">
                    <li><a href="/Project">プロジェクト</a></li>
                    <li><a href="/Status">営業状況</a></li>
                    <li><a href="/Alert">未完了アラート</a></li>
                    <li><a href="/MilestoneLog">変更ログ</a></li>
                  </ul>
                </div><!--/.nav-collapse -->
              </div>
            </div>

            <div style="background-color: #ffffff; padding: 10px; margin-bottom: 20px;">
{{ section contents }}
{{ end section contents }}
            </div>

        </div><!--/.container-->
{{ end section body }}