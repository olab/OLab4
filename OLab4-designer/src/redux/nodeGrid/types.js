// @flow
import type { Node } from '../../components/NodeGrid/types';

const UPDATE_NODE_GRID_SUCCEEDED = 'UPDATE_NODE_GRID_SUCCEEDED';
type UpdateNodeGridSucceeded = {
  type: 'UPDATE_NODE_GRID_SUCCEEDED',
  nodes: Array<Node>,
};

const UPDATE_NODE_GRID_FAILED = 'UPDATE_NODE_GRID_FAILED';
type UpdateNodeGridFailed = {
  type: 'UPDATE_NODE_GRID_FAILED',
};

const UPDATE_NODE_GRID_REQUESTED = 'UPDATE_NODE_GRID_REQUESTED';
type UpdateNodeGridRequested = {
  type: 'UPDATE_NODE_GRID_REQUESTED',
  nodes: Array<Node>,
};

export type NodeGrid = UpdateNodeGridSucceeded | UpdateNodeGridFailed |
  UpdateNodeGridRequested;

export {
  UPDATE_NODE_GRID_REQUESTED,
  UPDATE_NODE_GRID_SUCCEEDED,
  UPDATE_NODE_GRID_FAILED,
};
