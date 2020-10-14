import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { CssBaseline } from '@material-ui/core';
import store, { history } from './store/store';
import * as serviceWorker from './serviceWorker';
import { ACTION_USER_AUTH_SUCCEEDED } from './redux/login/action';

import App from './components';
import GlobalStyles from './styles';

const token = localStorage.getItem('token');
if (token) {
  store.dispatch(ACTION_USER_AUTH_SUCCEEDED(token));
}

const target = document.getElementById('root');

const Root = (
  <Provider store={store}>
    <CssBaseline />
    <GlobalStyles />
    <App history={history} />
  </Provider>
);

ReactDOM.render(Root, target);

serviceWorker.register();

// If you want your app to work offline and load faster, you can change
// unregister() to register() below. Note this comes with some pitfalls.
// Learn more about service workers: http://bit.ly/CRA-PWA
serviceWorker.unregister();
