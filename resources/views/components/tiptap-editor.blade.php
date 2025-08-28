@props([
    'name' => 'content',
    'id' => null,
    'value' => '',
    'height' => 400,
    'placeholder' => 'Escribe tu contenido aquí...',
    'toolbar' => true
])

@php
    $id = $id ?? $name;
@endphp

<div class="tiptap-wrapper" x-data="tiptapEditor('{{ $id }}', '{{ $value }}', {{ $height }}, '{{ $placeholder }}')">
    @if($toolbar)
        <!-- Barra de herramientas -->
        <div class="tiptap-toolbar bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-t-lg p-2 mb-2">
            <div class="flex flex-wrap items-center gap-2">
                <!-- Formato de texto -->
                <button type="button" @click="editor.chain().focus().toggleBold().run()" 
                        :class="{ 'bg-blue-500 text-white': editor.isActive('bold') }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M12.6 18.6c-1.3 0-2.5-.3-3.5-.9-.3-.2-.6-.4-.8-.7-.2-.3-.4-.6-.5-1-.1-.4-.2-.8-.2-1.3V4.5c0-.5.1-.9.2-1.3.1-.4.3-.7.5-1 .2-.3.5-.5.8-.7.3-.2.6-.4.9-.5.3-.1.6-.2 1-.2.4 0 .8.1 1.1.2.3.1.6.3.9.5.3.2.5.4.7.7.2.3.3.6.4 1 .1.4.2.8.2 1.3v10.8c0 .5-.1.9-.2 1.3-.1.4-.2.7-.4 1-.2.3-.4.5-.7.7-.2.2-.5.4-.9.5-.3.1-.7.2-1.1.2z"/>
                    </svg>
                </button>
                
                <button type="button" @click="editor.chain().focus().toggleItalic().run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive('italic') }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 3a1 1 0 011-1h4a1 1 0 110 2h-1.5l-1.5 14h1a1 1 0 110 2H8a1 1 0 110-2h1.5l1.5-14H9a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <button type="button" @click="editor.chain().focus().toggleUnderline().run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive('underline') }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <button type="button" @click="editor.chain().focus().toggleStrike().run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive('strike') }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>
                
                <!-- Alineación -->
                <button type="button" @click="editor.chain().focus().setTextAlign('left').run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive({ textAlign: 'left' }) }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <button type="button" @click="editor.chain().focus().setTextAlign('center').run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive({ textAlign: 'center' }) }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <button type="button" @click="editor.chain().focus().setTextAlign('right').run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive({ textAlign: 'right' }) }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>
                
                <!-- Listas -->
                <button type="button" @click="editor.chain().focus().toggleBulletList().run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive('bulletList') }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
                
                <button type="button" @click="editor.chain().focus().toggleOrderedList().run()"
                        :class="{ 'bg-blue-500 text-white': editor.isActive('orderedList') }"
                        class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif
    
    <!-- Área del editor -->
    <div id="{{ $id }}" class="tiptap-editor bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"></div>
    
    <!-- Campo oculto para el formulario -->
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" />
</div>

<script>
function tiptapEditor(id, content, height, placeholder) {
    return {
        editor: null,
        content: content,
        
        init() {
            this.$nextTick(() => {
                this.initEditor()
            })
        },
        
        initEditor() {
            const element = document.getElementById(id)
            if (!element) return
            
            // Crear el editor
            this.editor = new window.TiptapEditor(element, {
                content: this.content,
                placeholder: placeholder,
                height: height
            })
            
            // Escuchar cambios
            element.addEventListener('tiptap:change', (event) => {
                this.content = event.detail.content
                this.updateHiddenField()
                this.triggerFormEvent()
            })
        },
        
        updateHiddenField() {
            const hiddenField = this.$el.querySelector(`input[name="${id}"]`)
            if (hiddenField) {
                hiddenField.value = this.content
            }
        },
        
        triggerFormEvent() {
            // Disparar evento para que el formulario sepa que cambió
            this.$el.dispatchEvent(new Event('input', { bubbles: true }))
        },
        
        getContent() {
            return this.editor ? this.editor.getContent() : ''
        },
        
        setContent(content) {
            if (this.editor) {
                this.editor.setContent(content)
                this.content = content
                this.updateHiddenField()
            }
        },
        
        insertContent(content) {
            if (this.editor) {
                this.editor.insertContent(content)
            }
        }
    }
}
</script>
