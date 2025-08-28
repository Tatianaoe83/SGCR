import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import TextAlign from '@tiptap/extension-text-align'
import Underline from '@tiptap/extension-underline'

class TiptapEditor {
    constructor(element, options = {}) {
        this.element = element
        this.options = {
            content: '',
            placeholder: 'Escribe tu contenido aquí...',
            height: 400,
            ...options
        }
        
        this.editor = null
        this.init()
    }

    init() {
        // Crear el editor
        this.editor = new Editor({
            element: this.element,
            extensions: [
                StarterKit,
                Placeholder.configure({
                    placeholder: this.options.placeholder,
                }),
                TextAlign.configure({
                    types: ['heading', 'paragraph'],
                }),
                Underline,
            ],
            content: this.options.content,
            onUpdate: ({ editor }) => {
                this.triggerChangeEvent(editor.getHTML())
            },
            editorProps: {
                attributes: {
                    class: 'prose prose-sm sm:prose lg:prose-lg xl:prose-2xl mx-auto focus:outline-none',
                },
            },
        })

        // Agregar estilos al editor
        this.element.style.minHeight = `${this.options.height}px`
        this.element.style.border = '1px solid #d1d5db'
        this.element.style.borderRadius = '0.5rem'
        this.element.style.padding = '1rem'
        this.element.style.fontSize = '14px'
        this.element.style.lineHeight = '1.6'
    }

    getContent() {
        return this.editor ? this.editor.getHTML() : ''
    }

    setContent(content) {
        if (this.editor) {
            this.editor.commands.setContent(content)
        }
    }

    insertContent(content) {
        if (this.editor) {
            this.editor.commands.insertContent(content)
        }
    }

    destroy() {
        if (this.editor) {
            this.editor.destroy()
        }
    }

    triggerChangeEvent(content) {
        // Disparar evento personalizado
        const event = new CustomEvent('tiptap:change', {
            detail: { content }
        })
        this.element.dispatchEvent(event)
    }
}

// Hacer disponible globalmente
window.TiptapEditor = TiptapEditor
