// @flow
import React from 'react';

import type { Node as NodeType } from './Constructor/Graph/Node/types';

export type IAppProps = {
  history: any,
  mapId: number,
  isAuth: boolean,
  nodes: Array<NodeType>,
  ACTION_SYNC_NODE_MIDDLEWARE: Function,
};

export type IProtectedRouteProps = {
  path: string,
  isAuth: boolean,
  exact?: boolean,
  scopedObject?: string,
  component: React.Node,
};
