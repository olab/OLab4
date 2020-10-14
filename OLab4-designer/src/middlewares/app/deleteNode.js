import { ACTION_DELETE_NODE_REQUESTED } from '../../redux/map/action';
import { ACTION_NOTIFICATION_INFO } from '../../redux/notifications/action';

import { MESSAGES } from '../../redux/notifications/config';
import { ROOT_TYPE as ROOT_NODE_TYPE } from '../../components/Constructor/Graph/Node/config';

import { DELETE_NODE_MIDDLEWARE } from './types';

const deleteNodeMiddleware = store => next => (action) => {
  if (DELETE_NODE_MIDDLEWARE === action.type) {
    const { nodeId, mapId, nodeType } = action;
    const isRootNode = nodeType === ROOT_NODE_TYPE;
    const actionToDispatch = isRootNode
      ? ACTION_NOTIFICATION_INFO(MESSAGES.ON_DELETE.NODE.INFO)
      : ACTION_DELETE_NODE_REQUESTED(nodeId, mapId);

    store.dispatch(actionToDispatch);
  }

  next(action);
};

export default deleteNodeMiddleware;
