// @flow
import type { Edge as EdgeType } from '../../components/Constructor/Graph/Edge/types';
import type { Node as NodeType } from '../../components/Constructor/Graph/Node/types';

export type Position = {
  x: number,
  y: number,
};

export type MapItem = {
  nodes: Array<NodeType>,
  edges: Array<EdgeType>,
};

export type Map = {
  nodes: Array<NodeType>,
  edges: Array<EdgeType>,
  undo: Array<MapItem>,
  redo: Array<MapItem>,
  isFetching: boolean,
  isUpdating: boolean,
  isDeleting: boolean,
};

const GET_NODE_REQUESTED = 'GET_NODE_REQUESTED';
type GetNodeRequested = {
  type: 'GET_NODE_REQUESTED',
  mapId: number,
  nodeId: number,
};

const FOCUS_NODE = 'FOCUS_NODE';
type FocusNode = {
  type: 'FOCUS_NODE',
  index: number,
  nodes: Array<NodeType>,
};

const UNFOCUS_NODE = 'UNFOCUS_NODE';
type UnfocusNode = {
  type: 'UNFOCUS_NODE',
  index: number,
  node: NodeType,
};

const SELECT_NODE = 'SELECT_NODE';
type SelectNode = {
  type: 'SELECT_NODE',
  nodes: Array<NodeType>,
};

const CREATE_NODE = 'CREATE_NODE';
type CreateNode = {
  type: 'CREATE_NODE',
  node: NodeType,
};

const CREATE_NODE_WITH_EDGE = 'CREATE_NODE_WITH_EDGE';
type CreateNodeWithEdge = {
  type: 'CREATE_NODE_WITH_EDGE',
  node: NodeType,
  edge: EdgeType,
  sourceNodeId: number,
};

const UPDATE_NODE = 'UPDATE_NODE';
type UpdateNode = {
  type: 'UPDATE_NODE',
  index: number,
  node: NodeType,
  isShowNotification: boolean,
};

const DELETE_NODE_REQUESTED = 'DELETE_NODE_REQUESTED';
type DeleteNodeRequested = {
  type: 'DELETE_NODE_REQUESTED',
  mapId?: number,
  nodeId: number,
  nodeIndex: number,
  edges: Array<EdgeType>,
};

const DELETE_NODE_FULLFILLED = 'DELETE_NODE_FULLFILLED';
type DeleteNodeFullFilled = {
  type: 'DELETE_NODE_FULLFILLED',
};

const DELETE_NODE_SYNC = 'DELETE_NODE_SYNC';
type DeleteNodeSync = {
  type: 'DELETE_NODE_SYNC',
  edges: Array<EdgeType>,
  nodeIndex: number,
};

const EXCHANGE_NODE_ID = 'EXCHANGE_NODE_ID';
type ExchangeNodeId = {
  type: 'EXCHANGE_NODE_ID',
  nodeIndex: number,
  node: NodeType,
  edges: Array<EdgeType>,
};

const EXCHANGE_EDGE_ID = 'EXCHANGE_EDGE_ID';
type ExchangeEdgeId = {
  type: 'EXCHANGE_EDGE_ID',
  index: number,
  edge: EdgeType,
};

const SELECT_EDGE = 'SELECT_EDGE';
type SelectEdge = {
  type: 'SELECT_EDGE',
  edges: Array<EdgeType>,
};

const CREATE_EDGE = 'CREATE_EDGE';
type CreateEdge = {
  type: 'CREATE_EDGE',
  edge: EdgeType,
};

const DELETE_EDGE = 'DELETE_EDGE';
type DeleteEdge = {
  type: 'DELETE_EDGE',
  index: number,
  edgeId: number,
  nodeId: number,
};

const UPDATE_EDGE = 'UPDATE_EDGE';
type UpdateEdge = {
  type: 'UPDATE_EDGE',
  index: number,
  edge: EdgeType,
};

const UPDATE_EDGE_VISUAL = 'UPDATE_EDGE_VISUAL';
type UpdateEdgeVisual = {
  type: 'UPDATE_EDGE_VISUAL',
  index: number,
  edge: EdgeType,
};

const EXTEND_MAP_REQUESTED = 'EXTEND_MAP_REQUESTED';
type ExtendMapRequested = {
  type: 'EXTEND_MAP_REQUESTED',
  templateId: number,
};

const EXTEND_MAP_FAILED = 'EXTEND_MAP_FAILED';
type ExtendMapFailed = {
  type: 'EXTEND_MAP_FAILED',
};

const EXTEND_MAP_SUCCEEDED = 'EXTEND_MAP_SUCCEEDED';
type ExtendMapSucceeded = {
  type: 'EXTEND_MAP_SUCCEEDED',
  nodes: Array<NodeType>,
  edges: Array<EdgeType>,
};

const UNDO_MAP = 'UNDO_MAP';
type UndoMap = {
  type: 'UNDO_MAP',
  currentMap: MapItem,
  prev: MapItem,
}

const REDO_MAP = 'REDO_MAP';
type RedoMap = {
  type: 'REDO_MAP',
  currentMap: MapItem,
  next: MapItem,
};

const GET_NODE_FULLFILLED = 'GET_NODE_FULLFILLED';
type GetNodeFullfilled = {
  type: 'GET_NODE_FULLFILLED',
  index: number,
  node: NodeType,
};

const GET_MAP_SUCCEEDED = 'GET_MAP_SUCCEEDED';
type GetMapSucceeded = {
  type: 'GET_MAP_SUCCEEDED',
  nodes: Array<NodeType>,
  edges: Array<EdgeType>,
};

const GET_MAP_FAILED = 'GET_MAP_FAILED';
type GetMapFailed = {
  type: 'GET_MAP_FAILED',
};

const GET_MAP_REQUESTED = 'GET_MAP_REQUESTED';
type GetMapRequested = {
  type: 'GET_MAP_REQUESTED',
  mapId: string,
};

const CREATE_MAP_SUCCEEDED = 'CREATE_MAP_SUCCEEDED';
type CreateMapFromTemplateSucceeded = {
  type: 'CREATE_MAP_SUCCEEDED',
  nodes: Array<NodeType>,
  edges: Array<EdgeType>,
};

const CREATE_MAP_FAILED = 'CREATE_MAP_FAILED';
type CreateMapFromTemplateFailed = {
  type: 'CREATE_MAP_FAILED',
};

const CREATE_MAP_REQUESTED = 'CREATE_MAP_REQUESTED';
type CreateMapFromTemplateRequested = {
  type: 'CREATE_MAP_REQUESTED',
  templateId?: number,
};

export type MapActions = SelectNode | SelectEdge |
  CreateNode | UpdateNode | DeleteNodeRequested |
  CreateEdge | DeleteEdge | UpdateEdge |
  UndoMap | RedoMap | ExchangeNodeId |
  GetMapSucceeded | GetMapFailed | GetMapRequested |
  CreateMapFromTemplateRequested | CreateMapFromTemplateSucceeded |
  CreateMapFromTemplateFailed | CreateNodeWithEdge | ExchangeEdgeId |
  ExtendMapRequested | ExtendMapFailed | ExtendMapSucceeded |
  UpdateEdgeVisual | FocusNode | UnfocusNode |
  GetNodeRequested | GetNodeFullfilled | DeleteNodeFullFilled |
  DeleteNodeSync;

export {
  GET_NODE_REQUESTED,
  GET_NODE_FULLFILLED,
  FOCUS_NODE,
  UNFOCUS_NODE,
  SELECT_NODE,
  CREATE_NODE,
  UPDATE_NODE,
  DELETE_NODE_SYNC,
  DELETE_NODE_REQUESTED,
  DELETE_NODE_FULLFILLED,
  EXCHANGE_NODE_ID,
  EXCHANGE_EDGE_ID,
  SELECT_EDGE,
  CREATE_EDGE,
  DELETE_EDGE,
  UPDATE_EDGE,
  UPDATE_EDGE_VISUAL,
  CREATE_NODE_WITH_EDGE,
  EXTEND_MAP_REQUESTED,
  EXTEND_MAP_FAILED,
  EXTEND_MAP_SUCCEEDED,
  UNDO_MAP,
  REDO_MAP,
  GET_MAP_FAILED,
  GET_MAP_SUCCEEDED,
  GET_MAP_REQUESTED,
  CREATE_MAP_FAILED,
  CREATE_MAP_SUCCEEDED,
  CREATE_MAP_REQUESTED,
};
