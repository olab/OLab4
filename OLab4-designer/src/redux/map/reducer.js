// @flow
import {
  type MapActions,
  type Map as MapType,
  SELECT_NODE,
  CREATE_NODE,
  UPDATE_NODE,
  DELETE_NODE_REQUESTED,
  DELETE_NODE_FULLFILLED,
  DELETE_NODE_SYNC,
  FOCUS_NODE,
  UNFOCUS_NODE,
  SELECT_EDGE,
  CREATE_EDGE,
  DELETE_EDGE,
  EXCHANGE_NODE_ID,
  EXCHANGE_EDGE_ID,
  CREATE_NODE_WITH_EDGE,
  UPDATE_EDGE,
  UPDATE_EDGE_VISUAL,
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
  GET_NODE_REQUESTED,
  GET_NODE_FULLFILLED,
} from './types';
import {
  UPDATE_NODE_GRID_REQUESTED,
  UPDATE_NODE_GRID_SUCCEEDED,
  UPDATE_NODE_GRID_FAILED,
} from '../nodeGrid/types';
import { SAVE_MAP_TO_UNDO } from '../../middlewares/core/types';

export const initialMapState: MapType = {
  nodes: [],
  edges: [],
  undo: [],
  redo: [],
  isFetching: false,
  isUpdating: false,
  isDeleting: false,
};

const map = (state: MapType = initialMapState, action: MapActions) => {
  switch (action.type) {
    case SAVE_MAP_TO_UNDO: {
      const { undo, ...restState } = state;
      const { currentMap } = action;

      return {
        ...restState,
        undo: [
          ...undo,
          currentMap,
        ],
        redo: [],
      };
    }
    case UNDO_MAP: {
      const { undo, redo, ...restState } = state;
      const { currentMap, prev: { nodes, edges } } = action;

      return {
        ...restState,
        nodes,
        edges,
        undo: [
          ...undo.slice(0, undo.length - 1),
        ],
        redo: [
          ...redo,
          currentMap,
        ],
      };
    }
    case REDO_MAP: {
      const { undo, redo, ...restState } = state;
      const { currentMap, next: { nodes, edges } } = action;

      return {
        ...restState,
        nodes,
        edges,
        redo: [
          ...redo.slice(0, redo.length - 1),
        ],
        undo: [
          ...undo,
          currentMap,
        ],
      };
    }
    case GET_NODE_REQUESTED:
    case GET_MAP_REQUESTED:
    case CREATE_MAP_REQUESTED:
    case EXTEND_MAP_REQUESTED:
      return {
        ...state,
        isFetching: true,
      };
    case UPDATE_NODE_GRID_REQUESTED:
      return {
        ...state,
        isUpdating: true,
      };
    case GET_MAP_FAILED:
    case CREATE_MAP_FAILED:
    case EXTEND_MAP_FAILED:
      return {
        ...state,
        isFetching: false,
      };
    case DELETE_NODE_FULLFILLED:
      return {
        ...state,
        isDeleting: false,
      };
    case UPDATE_NODE_GRID_FAILED:
      return {
        ...state,
        isUpdating: false,
      };
    case GET_MAP_SUCCEEDED:
    case CREATE_MAP_SUCCEEDED: {
      const { nodes, edges = [] } = action;

      return {
        ...state,
        nodes,
        edges,
        isFetching: false,
      };
    }
    case EXTEND_MAP_SUCCEEDED: {
      const { nodes, edges } = action;

      return {
        ...state,
        nodes,
        edges,
        isFetching: false,
      };
    }
    case GET_NODE_FULLFILLED: {
      const { index, node } = action;
      const { nodes, ...restState } = state;

      return {
        ...restState,
        nodes: [
          ...nodes.slice(0, index),
          node,
          ...nodes.slice(index + 1),
        ],
        isFetching: false,
      };
    }
    case UPDATE_NODE_GRID_SUCCEEDED: {
      const { nodes } = action;

      return {
        ...state,
        nodes,
        isUpdating: false,
      };
    }
    case SELECT_NODE:
    case FOCUS_NODE: {
      const { nodes } = action;

      return {
        ...state,
        nodes,
      };
    }
    case UPDATE_NODE:
    case UNFOCUS_NODE: {
      const { nodes, ...restState } = state;
      const { index, node } = action;

      return {
        ...restState,
        nodes: [
          ...nodes.slice(0, index),
          node,
          ...nodes.slice(index + 1),
        ],
      };
    }
    case CREATE_NODE: {
      const { nodes, ...restState } = state;
      const { node } = action;

      return {
        ...restState,
        nodes: [
          ...nodes,
          node,
        ],
      };
    }
    case EXCHANGE_NODE_ID: {
      const { nodes, ...restState } = state;
      const { nodeIndex, node, edges } = action;

      return {
        ...restState,
        nodes: [
          ...nodes.slice(0, nodeIndex),
          node,
          ...nodes.slice(nodeIndex + 1),
        ],
        edges,
      };
    }
    case DELETE_NODE_REQUESTED: {
      const { nodes, ...restState } = state;
      const { nodeIndex, edges } = action;

      return {
        ...restState,
        nodes: [
          ...nodes.slice(0, nodeIndex),
          ...nodes.slice(nodeIndex + 1),
        ],
        edges,
        isDeleting: true,
      };
    }
    case DELETE_NODE_SYNC: {
      const { nodes, ...restState } = state;
      const { edges, nodeIndex } = action;

      return {
        ...restState,
        edges,
        nodes: [
          ...nodes.slice(0, nodeIndex),
          ...nodes.slice(nodeIndex + 1),
        ],
      };
    }
    case CREATE_NODE_WITH_EDGE: {
      const { nodes, edges, ...restState } = state;
      const { node, edge } = action;

      return {
        ...restState,
        nodes: [
          ...nodes,
          node,
        ],
        edges: [
          ...edges,
          edge,
        ],
      };
    }
    case SELECT_EDGE: {
      const { edges } = action;

      return {
        ...state,
        edges,
      };
    }
    case EXCHANGE_EDGE_ID:
    case UPDATE_EDGE_VISUAL:
    case UPDATE_EDGE: {
      const { edges, ...restState } = state;
      const { index, edge } = action;

      return {
        ...restState,
        edges: [
          ...edges.slice(0, index),
          edge,
          ...edges.slice(index + 1),
        ],
      };
    }
    case CREATE_EDGE: {
      const { edges, ...restState } = state;
      const { edge } = action;

      return {
        ...restState,
        edges: [
          ...edges,
          edge,
        ],
      };
    }
    case DELETE_EDGE: {
      const { edges, ...restState } = state;
      const { index } = action;

      return {
        ...restState,
        edges: [
          ...edges.slice(0, index),
          ...edges.slice(index + 1),
        ],
      };
    }
    default:
      return state;
  }
};

export default map;
