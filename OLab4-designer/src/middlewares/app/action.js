// @flow
import {
  SYNC_NODE_MIDDLEWARE,
  GET_WHOLE_MAP_MIDDLEWARE,
  DELETE_NODE_MIDDLEWARE,
} from './types';

export const ACTION_GET_WHOLE_MAP_MIDDLEWARE = (mapId: number) => ({
  type: GET_WHOLE_MAP_MIDDLEWARE,
  mapId,
});

export const ACTION_SYNC_NODE_MIDDLEWARE = (
  mapId: number,
  nodeId: number,
  actionType: string,
) => ({
  type: SYNC_NODE_MIDDLEWARE,
  mapId,
  nodeId,
  actionType,
});

export const ACTION_DELETE_NODE_MIDDLEWARE = (
  mapId: number,
  nodeId: number,
  nodeType: number,
) => ({
  type: DELETE_NODE_MIDDLEWARE,
  mapId,
  nodeId,
  nodeType,
});
