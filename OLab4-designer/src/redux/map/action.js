// @flow
import cloneDeep from 'lodash.clonedeep';
import store from '../../store/store';

import type { Node as NodeType } from '../../components/Constructor/Graph/Node/types';
import type { Edge as EdgeType } from '../../components/Constructor/Graph/Edge/types';
import {
  GET_NODE_REQUESTED,
  GET_NODE_FULLFILLED,
  SELECT_NODE,
  CREATE_NODE,
  UPDATE_NODE,
  DELETE_NODE_REQUESTED,
  DELETE_NODE_FULLFILLED,
  DELETE_NODE_SYNC,
  SELECT_EDGE,
  CREATE_EDGE,
  DELETE_EDGE,
  FOCUS_NODE,
  UNFOCUS_NODE,
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
} from './types';

export const ACTION_GET_NODE_REQUESTED = (mapId: number, nodeId: number) => ({
  type: GET_NODE_REQUESTED,
  mapId,
  nodeId,
});

export const ACTION_GET_NODE_FULLFILLED = (initialNode: NodeType) => {
  const { map: { nodes } } = store.getState();
  const {
    isFocused = false,
    isSelected = false,
  } = nodes.find(item => item.id === initialNode.id) || {};
  const index = nodes.findIndex(({ id }) => id === initialNode.id);
  const node = {
    ...initialNode,
    isFocused,
    isSelected,
  };

  return {
    type: GET_NODE_FULLFILLED,
    index,
    node,
  };
};

export const ACTION_FOCUS_NODE = (nodeId: number) => {
  const { map: { nodes } } = store.getState();
  const clonedNodes = nodes.map((node) => {
    if (node.id === nodeId) {
      return {
        ...node,
        isFocused: true,
        isSelected: false,
      };
    }

    if (node.isFocused || node.isSelected) {
      return {
        ...node,
        isFocused: false,
        isSelected: false,
      };
    }

    return node;
  });

  return {
    type: FOCUS_NODE,
    nodes: clonedNodes,
  };
};

export const ACTION_UNFOCUS_NODE = (nodeId: number) => {
  const { map: { nodes } } = store.getState();
  const index = nodes.findIndex(({ id }) => id === nodeId);
  const node = {
    ...nodes[index],
    isFocused: false,
  };

  return {
    type: UNFOCUS_NODE,
    index,
    node,
  };
};

export const ACTION_SELECT_NODE = (nodeId: number | null) => {
  const { map: { nodes } } = store.getState();
  const clonedNodes = nodes.map((node) => {
    if (nodeId && node.id === nodeId) {
      return {
        ...node,
        isSelected: true,
        isFocused: false,
      };
    }

    if (node.isSelected || node.isFocused) {
      return {
        ...node,
        isSelected: false,
        ...(nodeId && { isFocused: false }),
      };
    }

    return node;
  });

  return {
    type: SELECT_NODE,
    nodes: clonedNodes,
  };
};

export const ACTION_SELECT_EDGE = (edgeId: number | null) => {
  const { map: { edges } } = store.getState();
  const clonedEdges = edges.map((edge) => {
    if (edgeId && edge.id === edgeId && !edge.isSelected) {
      return {
        ...edge,
        isSelected: true,
      };
    }

    if (edge.isSelected) {
      return {
        ...edge,
        isSelected: false,
      };
    }

    return edge;
  });

  return {
    type: SELECT_EDGE,
    edges: clonedEdges,
  };
};

export const ACTION_CREATE_NODE = (node: NodeType) => ({
  type: CREATE_NODE,
  node,
});

export const ACTION_UPDATE_NODE_COLLAPSE = (nodeId: number) => {
  const { map: { nodes } } = store.getState();
  const index = nodes.findIndex(({ id }) => id === nodeId);
  const node = {
    ...nodes[index],
    isCollapsed: !nodes[index].isCollapsed,
  };

  return {
    type: UPDATE_NODE,
    index,
    node,
  };
};

export const ACTION_UPDATE_NODE_LOCK = (nodeId: number) => {
  const { map: { nodes } } = store.getState();
  const index = nodes.findIndex(({ id }) => id === nodeId);
  const node = {
    ...nodes[index],
    isLocked: !nodes[index].isLocked,
  };

  return {
    type: UPDATE_NODE,
    index,
    node,
  };
};

export const ACTION_CREATE_NODE_WITH_EDGE = (
  node: NodeType,
  edge: EdgeType,
  sourceNodeId: number,
) => ({
  type: CREATE_NODE_WITH_EDGE,
  node,
  edge,
  sourceNodeId,
});

export const ACTION_UPDATE_NODE_RESIZE = (nodeId: number, width: number, height: number) => {
  const { map: { nodes } } = store.getState();
  const index = nodes.findIndex(({ id }) => id === nodeId);
  const node = {
    ...nodes[index],
    width,
    height,
  };

  return {
    type: UPDATE_NODE,
    index,
    node,
  };
};

export const ACTION_UPDATE_NODE = (
  nodeData: NodeType,
  isShowNotification: boolean = false,
  mapIdFromURL: number,
) => {
  const { map: { nodes } } = store.getState();
  const index = nodes.findIndex(({ id }) => id === nodeData.id);
  const node = {
    ...nodes[index],
    ...nodeData,
  };

  return {
    type: UPDATE_NODE,
    index,
    node,
    isShowNotification,
    mapIdFromURL,
  };
};

export const ACTION_DELETE_NODE_REQUESTED = (
  nodeId: number,
  mapId?: number,
) => {
  const { map: { nodes, edges } } = store.getState();
  const nodeIndex = nodes.findIndex(({ id }) => id === nodeId);
  const filteredEdges = edges.filter(({ source, target }) => (
    source !== nodeId && target !== nodeId
  ));

  return {
    type: DELETE_NODE_REQUESTED,
    edges: filteredEdges,
    mapId,
    nodeId,
    nodeIndex,
  };
};

export const ACTION_DELETE_NODE_FULLFILLED = () => ({
  type: DELETE_NODE_FULLFILLED,
});

export const ACTION_DELETE_NODE_SYNC = (
  nodeId: number,
) => {
  const { map: { edges, nodes } } = store.getState();
  const nodeIndex = nodes.findIndex(({ id }) => id === nodeId);
  const filteredEdges = edges.filter(({ source, target }) => (
    source !== nodeId && target !== nodeId
  ));

  return ({
    type: DELETE_NODE_SYNC,
    edges: filteredEdges,
    nodeIndex,
  });
};

export const ACTION_EXCHANGE_NODE_ID = (oldNodeId: number | string, newNodeId: number) => {
  const { map: { nodes, edges } } = store.getState();
  const nodeIndex = nodes.findIndex(({ id }) => id === oldNodeId);

  const clonedNode = {
    ...nodes[nodeIndex],
    id: newNodeId,
  };

  const clonedEdges = edges.map((edge) => {
    const { source, target } = edge;

    if (target === oldNodeId || source === oldNodeId) {
      const clonedEdge = { ...edge };

      if (target === oldNodeId) {
        clonedEdge.target = newNodeId;
      } else if (source === oldNodeId) {
        clonedEdge.source = newNodeId;
      }

      return clonedEdge;
    }

    return edge;
  });

  return {
    type: EXCHANGE_NODE_ID,
    nodeIndex,
    node: clonedNode,
    edges: clonedEdges,
  };
};

export const ACTION_EXCHANGE_EDGE_ID = (oldEdgeId: number | string, newEdgeId: number) => {
  const { map: { edges } } = store.getState();
  const index = edges.findIndex(({ id }) => id === oldEdgeId);
  const edge = {
    ...edges[index],
    id: newEdgeId,
  };

  return {
    type: EXCHANGE_EDGE_ID,
    index,
    edge,
  };
};

export const ACTION_CREATE_EDGE = (edge: EdgeType) => ({
  type: CREATE_EDGE,
  edge,
});

export const ACTION_UPDATE_EDGE = (edge: EdgeType, isVisualOnly: boolean = false) => {
  const { map: { edges } } = store.getState();
  const edgeIndex = edges.findIndex(({ id }) => id === edge.id);
  const clonedEdge = {
    ...edges[edgeIndex],
    ...edge,
  };

  return {
    type: isVisualOnly
      ? UPDATE_EDGE_VISUAL
      : UPDATE_EDGE,
    index: edgeIndex,
    edge: clonedEdge,
  };
};

export const ACTION_DELETE_EDGE = (edgeId: number, nodeId: number) => {
  const { map: { edges } } = store.getState();
  const index = edges.findIndex(({ id }) => id === edgeId);

  return {
    type: DELETE_EDGE,
    index,
    edgeId,
    nodeId,
  };
};

export const ACTION_UNDO_MAP = () => {
  const { map: { undo, nodes, edges } } = store.getState();
  const prev = undo[undo.length - 1];
  const currentMap = cloneDeep({ nodes, edges });

  return {
    type: UNDO_MAP,
    currentMap,
    prev,
  };
};

export const ACTION_REDO_MAP = () => {
  const { map: { redo, nodes, edges } } = store.getState();
  const next = redo[redo.length - 1];
  const currentMap = cloneDeep({ nodes, edges });

  return {
    type: REDO_MAP,
    currentMap,
    next,
  };
};

export const ACTION_GET_MAP_FAILED = () => ({
  type: GET_MAP_FAILED,
});

export const ACTION_GET_MAP_SUCCEEDED = (nodes: Array<NodeType>, edges: Array<EdgeType>) => ({
  type: GET_MAP_SUCCEEDED,
  nodes,
  edges,
});

export const ACTION_GET_MAP_REQUESTED = (mapId: string) => ({
  type: GET_MAP_REQUESTED,
  mapId,
});

export const ACTION_CREATE_MAP_FAILED = () => ({
  type: CREATE_MAP_FAILED,
});

export const ACTION_CREATE_MAP_SUCCEEDED = (nodes: Array<NodeType>, edges: Array<EdgeType>) => ({
  type: CREATE_MAP_SUCCEEDED,
  nodes,
  edges,
});

export const ACTION_CREATE_MAP_REQUESTED = (templateId?: number) => ({
  type: CREATE_MAP_REQUESTED,
  templateId,
});

export const ACTION_EXTEND_MAP_REQUESTED = (templateId: number) => ({
  type: EXTEND_MAP_REQUESTED,
  templateId,
});

export const ACTION_EXTEND_MAP_FAILED = () => ({
  type: EXTEND_MAP_FAILED,
});

export const ACTION_EXTEND_MAP_SUCCEEDED = (nodes: Array<NodeType>, edges: Array<EdgeType>) => ({
  type: EXTEND_MAP_SUCCEEDED,
  nodes,
  edges,
});
