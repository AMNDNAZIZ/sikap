/**
 * CKEditor 5 Initialization Helper
 * Menggantikan TinyMCE dengan CKEditor 5
 */

class CKEditorHelper {
  constructor() {
    this.editors = {};
  }

  /**
   * Inisialisasi CKEditor dengan konfigurasi standar
   * @param {string} selector - CSS selector untuk textarea
   * @param {object} customConfig - Konfigurasi tambahan
   * @returns {Promise} - Promise yang resolve dengan editor instance
   */
  async init(selector, customConfig = {}) {
    // Support both string selector and element
    const element = typeof selector === 'string' 
      ? document.querySelector(selector) 
      : selector;
      
    if (!element) {
      console.error('Element tidak ditemukan:', selector);
      return null;
    }

    const defaultConfig = {
      toolbar: {
        items: [
          'undo', 'redo',
          '|',
          'heading',
          '|',
          'bold', 'italic', 'underline', 'strikethrough',
          '|',
          'link', 'uploadImage', 'insertTable', 'blockQuote', 'mediaEmbed',
          '|',
          'bulletedList', 'numberedList', 'outdent', 'indent',
          '|',
          'alignment',
          '|',
          'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor',
          '|',
          'code', 'codeBlock',
          '|',
          'removeFormat', 'sourceEditing'
        ],
        shouldNotGroupWhenFull: true
      },
      language: 'id',
      image: {
        toolbar: [
          'imageTextAlternative', 'toggleImageCaption', 'imageStyle:inline',
          'imageStyle:block', 'imageStyle:side', 'linkImage'
        ]
      },
      table: {
        contentToolbar: [
          'tableColumn', 'tableRow', 'mergeTableCells',
          'tableCellProperties', 'tableProperties'
        ]
      },
      heading: {
        options: [
          { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
          { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
          { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
          { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
          { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
        ]
      },
      fontSize: {
        options: [9, 11, 13, 'default', 17, 19, 21, 24, 28, 32]
      },
      fontFamily: {
        options: [
          'default',
          'Arial, Helvetica, sans-serif',
          'Courier New, Courier, monospace',
          'Georgia, serif',
          'Lucida Sans Unicode, Lucida Grande, sans-serif',
          'Tahoma, Geneva, sans-serif',
          'Times New Roman, Times, serif',
          'Trebuchet MS, Helvetica, sans-serif',
          'Verdana, Geneva, sans-serif'
        ]
      },
      link: {
        decorators: {
          openInNewTab: {
            mode: 'manual',
            label: 'Buka di tab baru',
            attributes: {
              target: '_blank',
              rel: 'noopener noreferrer'
            }
          }
        }
      }
    };

    // Merge custom config dengan default config
    const config = this._mergeConfig(defaultConfig, customConfig);

    try {
      const editor = await ClassicEditor.create(element, config);
      
      // Store dengan selector string untuk konsistensi
      const selectorKey = typeof selector === 'string' ? selector : '#' + element.id;
      this.editors[selectorKey] = editor;
      
      return editor;
    } catch (error) {
      console.error('Error inisialisasi CKEditor:', error);
      return null;
    }
  }

  /**
   * Dapatkan instance editor berdasarkan selector
   * @param {string} selector - CSS selector
   * @returns {object|null} - Editor instance atau null
   */
  get(selector) {
    return this.editors[selector] || null;
  }

  /**
   * Dapatkan konten HTML dari editor
   * @param {string} selector - CSS selector
   * @returns {string} - HTML content
   */
  getContent(selector) {
    const editor = this.get(selector);
    return editor ? editor.getData() : '';
  }

  /**
   * Set konten HTML ke editor
   * @param {string} selector - CSS selector
   * @param {string} content - HTML content
   */
  setContent(selector, content) {
    const editor = this.get(selector);
    if (editor) {
      editor.setData(content);
    }
  }

  /**
   * Destroy editor instance
   * @param {string} selector - CSS selector
   */
  async destroy(selector) {
    const editor = this.get(selector);
    if (editor) {
      await editor.destroy();
      delete this.editors[selector];
    }
  }

  /**
   * Destroy semua editor instances
   */
  async destroyAll() {
    for (const selector in this.editors) {
      await this.destroy(selector);
    }
  }

  /**
   * Deep merge configuration objects
   * @private
   */
  _mergeConfig(target, source) {
    const output = { ...target };
    
    for (const key in source) {
      if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
        output[key] = this._mergeConfig(target[key] || {}, source[key]);
      } else {
        output[key] = source[key];
      }
    }
    
    return output;
  }
}

// Instance global
window.ckEditorHelper = new CKEditorHelper();
