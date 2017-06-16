// http://eslint.org/docs/user-guide/configuring

module.exports = {
  root: true,
  parserOptions: {
    sourceType: 'module',
    ecmaVersion: 2017
  },
  env: {
    browser: true,
  },
  extends: 'standard',
  // required to lint *.vue files
  plugins: [ 'html' ],
  
  // Custom Rules
  'rules': {
    // allow paren-less arrow functions
    'arrow-parens': 0,
    // allow async-await
    'generator-star-spacing': 0,
    // allow debugger during development
    'no-debugger': process.env.NODE_ENV === 'production' ? 2 : 0,
    
    'no-trailing-spaces': 'off',
    'padded-blocks': 'off',
    'brace-style': ["error", "stroustrup", { "allowSingleLine": true } ],
    'no-multiple-empty-lines': 'off'
  },
  
  // Custom globals
  "globals": {
    // "grecaptcha": true,
  }
}
