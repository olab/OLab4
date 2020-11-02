// @flow
import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, Redirect } from 'react-router-dom';
import { ConnectedRouter } from 'connected-react-router';
import { Notify } from 'react-redux-notify';

import Login from './Login';
import Home from './Home';
import Constructor from './Constructor';
import CounterGrid from './CounterGrid';
import NodeGrid from './NodeGrid';
import PageNotFound from './404';
import Header from './Header';
import SOEditor from './SOEditor';
import MapDetails from './MapDetails';
import AdvancedNodeEditor from './AdvancedNodeEditor';

import * as wholeMapActions from '../middlewares/app/action';

import type { IAppProps, IProtectedRouteProps } from './types';

import 'react-redux-notify/dist/ReactReduxNotify.css';

const ProtectedRoute = ({
  component: Component, isAuth, path, exact, ...rest
}: IProtectedRouteProps) => (
  <Route
    path={path}
    exact={exact}
    render={props => (isAuth ? (
      <Component {...props} {...rest} />
    ) : (
      <Redirect push to="/login" />
    ))}
  />
);

export class App extends PureComponent<IAppProps> {
  componentDidMount(): void {
    window.addEventListener('storage', this.handleStorageChange);
  }

  handleStorageChange = (event: Event): void => {
    const { newValue } = event;
    const { ACTION_SYNC_NODE_MIDDLEWARE } = this.props;
    const { nodeId, mapId, actionType } = JSON.parse(newValue);

    ACTION_SYNC_NODE_MIDDLEWARE(mapId, nodeId, actionType);
  }

  render() {
    const { isAuth, history } = this.props;

    return (
      <ConnectedRouter history={history}>
        <>
          <Header />
          <Switch>
            <Route exact path="/login" component={Login} />
            <ProtectedRoute exact isAuth={isAuth} path="/" component={Home} />
            <ProtectedRoute exact isAuth={isAuth} path="/:mapId" component={Constructor} />
            <ProtectedRoute exact isAuth={isAuth} path="/:mapId/nodegrid" component={NodeGrid} />
            <ProtectedRoute exact isAuth={isAuth} path="/:mapId/mapdetails" component={MapDetails} />
            <ProtectedRoute exact isAuth={isAuth} path="/:mapId/countergrid" component={CounterGrid} />
            <ProtectedRoute exact isAuth={isAuth} path="/:mapId/:nodeId/ane" component={AdvancedNodeEditor} />
            <ProtectedRoute isAuth={isAuth} path="/scopedObject/:scopedObjectType" component={SOEditor} />
            <ProtectedRoute exact isAuth={isAuth} path="*" component={PageNotFound} />
          </Switch>
          <Notify />
        </>
      </ConnectedRouter>
    );
  }
}

const mapStateToProps = ({
  user: { isAuth },
  map: { nodes },
}) => ({
  isAuth,
  nodes,
});

const mapDispatchToProps = dispatch => ({
  ACTION_SYNC_NODE_MIDDLEWARE: (mapId: number, nodeId: number, actionType: string) => {
    dispatch(wholeMapActions.ACTION_SYNC_NODE_MIDDLEWARE(mapId, nodeId, actionType));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(App);
