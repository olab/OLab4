import { createGlobalStyle } from 'styled-components';
import { YELLOW } from './shared/colors';

const GlobalStyle = createGlobalStyle`
  body {
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    overflow: hidden;

    &:fullscreen {
      header {
        display: none;
      }
    }

    .hide {
      display: none;
    }
  }

  code {
    font-family: source-code-pro, Menlo, Monaco, Consolas, 'Courier New',
    monospace;
  }

  foreignObject {
    overflow: visible;
  }

  span[class^="Notification__icon"] {
    padding-top: 6px;
  }

  /* clears the 'X' from Internet Explorer */
  input[type=search]::-ms-clear,
  input[type=search]::-ms-reveal {
    width: 0;
    height: 0;
    display: none;
  }

  /* clears the 'X' from Chrome */
  input[type="search"]::-webkit-search-decoration,
  input[type="search"]::-webkit-search-cancel-button,
  input[type="search"]::-webkit-search-results-button,
  input[type="search"]::-webkit-search-results-decoration {
    display: none;
  }

  div.tox-tinymce {
    border-radius: 5px;
  }

  div.tox-notifications-container {
    display: none;
  }

  mark {
    background-color: ${YELLOW},
  }
`;

export default GlobalStyle;
