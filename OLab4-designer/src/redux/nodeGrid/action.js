// @flow
import isEqual from 'lodash.isequal';
import differenceWith from 'lodash.differencewith';

import store from '../../store/store';

import { getNodesReduced } from '../../components/NodeGrid/utils';

import {
  UPDATE_NODE_GRID_REQUESTED,
  UPDATE_NODE_GRID_SUCCEEDED,
  UPDATE_NODE_GRID_FAILED,
} from './types';
import type { Node } from '../../components/NodeGrid/types';

export const ACTION_UPDATE_NODE_GRID_FAILED = () => ({
  type: UPDATE_NODE_GRID_FAILED,
});

export const ACTION_UPDATE_NODE_GRID_SUCCEEDED = (nodes: Node) => {
  const { map: { nodes: stateNodes } } = store.getState();

  const updatedNodes = stateNodes.map((stateNode: Node): Node => ({
    ...stateNode,
    ...nodes.find(node => node.id === stateNode.id),
  }));

  return ({
    type: UPDATE_NODE_GRID_SUCCEEDED,
    nodes: updatedNodes,
  });
};

export const ACTION_UPDATE_NODE_GRID_REQUESTED = (nodes: Array<Node>) => {
  const { map: { nodes: storeNodes } } = store.getState();
  const { nodes: reducedStoreNodes } = getNodesReduced(storeNodes);
  const diffNodes = differenceWith(nodes, reducedStoreNodes, isEqual);

  return ({
    type: UPDATE_NODE_GRID_REQUESTED,
    nodes: diffNodes,
  });
};
