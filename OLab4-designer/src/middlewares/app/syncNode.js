import {
  ACTION_GET_NODE_REQUESTED,
  ACTION_DELETE_NODE_SYNC,
} from '../../redux/map/action';

import { SYNC_NODE_MIDDLEWARE } from './types';
import { UPDATE_NODE, DELETE_NODE_REQUESTED } from '../../redux/map/types';

const syncNodeMiddleware = store => next => (action) => {
  if (SYNC_NODE_MIDDLEWARE === action.type) {
    const { actionType, nodeId, mapId } = action;
    const { map: { nodes } } = store.getState();
    const isNodeFound = nodes.some(node => node.id === nodeId);
    const shouldUpdateNode = actionType === UPDATE_NODE && isNodeFound;
    const shouldDeleteNode = actionType === DELETE_NODE_REQUESTED && isNodeFound;

    if (shouldUpdateNode) {
      store.dispatch(ACTION_GET_NODE_REQUESTED(mapId, nodeId));
    }

    if (shouldDeleteNode) {
      store.dispatch(ACTION_DELETE_NODE_SYNC(nodeId));
    }
  }

  next(action);
};

export default syncNodeMiddleware;
