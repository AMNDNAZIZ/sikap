/**
 * Suppress MathJax Console Errors
 * Add this script BEFORE MathJax is loaded
 */

// Override console.error to filter MathJax errors
(function() {
  const originalError = console.error;
  console.error = function(...args) {
    const errorString = args.join(' ');
    
    // Filter out MathJax "Cannot set property Package" error
    if (errorString.includes('Cannot set property Package') || 
        errorString.includes('tex-mml-chtml.js')) {
      // Silently ignore this error
      return;
    }
    
    // Pass through all other errors
    originalError.apply(console, args);
  };
})();
