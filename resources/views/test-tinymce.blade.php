<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test TinyMCE</title>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <h1>Test TinyMCE</h1>
    
    <x-tinymce 
        name="test_content" 
        id="test_editor"
        :height="300"
        placeholder="Escribe algo aquí..."
    />
    
    <button onclick="getContent()">Obtener Contenido</button>
    <div id="output"></div>
    
    <script>
        function getContent() {
            const editor = tinymce.get('test_editor');
            if (editor) {
                const content = editor.getContent();
                document.getElementById('output').innerHTML = '<h3>Contenido:</h3><div>' + content + '</div>';
            } else {
                document.getElementById('output').innerHTML = '<p>Editor no encontrado</p>';
            }
        }
    </script>
</body>
</html>
