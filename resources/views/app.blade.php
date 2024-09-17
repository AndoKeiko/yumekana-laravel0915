<!-- resources/views/app.blade.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My SPA Application</title>
    <!-- 必要なスタイルやスクリプトを読み込む -->
    @vite('resources/css/app.css')
</head>
<body>
    <div id="app"></div>
    <!-- ビルドされた JavaScript ファイルを読み込む -->
    @viteReactRefresh
    @vite('resources/js/app.jsx')
</body>
</html>
