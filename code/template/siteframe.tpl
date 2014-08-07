<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
        <meta http-equiv="X-UA-Compatible" content="IE=10" />
        <title>{{ $site.title }}</title>
        <meta name="keywords" content="{{ $site.keywords }}" /> 
        <meta name="description" content="{{ $site.description }}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
        {{ head }}
    </head>
    <body>
        {{ section body }}
        {{ end section body }}
    </body>
</html>
    