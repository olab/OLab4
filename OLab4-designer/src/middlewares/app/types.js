// @flow
export const GET_WHOLE_MAP_MIDDLEWARE = 'GET_WHOLE_MAP_MIDDLEWARE';
type GetWholeMapMiddleware = {
  type: 'GET_WHOLE_MAP_MIDDLEWARE',
  mapId: number,
};

export const SYNC_NODE_MIDDLEWARE = 'SYNC_NODE_MIDDLEWARE';
type SyncNodeMiddleware = {
  type: 'SYNC_NODE_MIDDLEWARE',
  mapId: number,
  nodeId: number,
  actionType: string,
};

export const DELETE_NODE_MIDDLEWARE = 'DELETE_NODE_MIDDLEWARE';
type DeleteNodeMiddleware = {
  type: 'DELETE_NODE_MIDDLEWARE',
  mapId: number,
  nodeId: number,
  actionType: string,
};

export type WholeMapActions = GetWholeMapMiddleware | SyncNodeMiddleware | DeleteNodeMiddleware;
