// @flow
import generateTmpId from '../../../helpers/generateTmpId';

import { SALT as NODE_SALT } from './Node/config';
import { SALT as EDGE_SALT } from './Edge/config';

import type {
  DefaultEdge as DefaultsEdgeType,
  DefaultNode as DefaultsNodeType,
} from '../../../redux/defaults/types';
import type { Node as NodeType } from './Node/types';
import type { Edge as EdgeType } from './Edge/types';

export const createNewNode = (
  mapId: number,
  x: number,
  y: number,
  defaultNodeBody: DefaultsNodeType,
): NodeType => {
  const newNodeId = generateTmpId(NODE_SALT);

  return {
    id: newNodeId,
    mapId,
    ...defaultNodeBody,
    x,
    y,
    isSelected: false,
    isFocused: false,
  };
};

export const createNewEdge = (
  sourceId: number,
  targetId: number,
  defaultEdgeBody: DefaultsEdgeType,
): EdgeType => {
  const newEdgeId = generateTmpId(EDGE_SALT);

  return {
    id: newEdgeId,
    source: sourceId,
    target: targetId,
    ...defaultEdgeBody,
    isSelected: false,
  };
};

export const spec = {
  drop: (props, monitor, component) => {
    if (!component) {
      return null;
    }

    return monitor.getDifferenceFromInitialOffset();
  },
};

export const collect = conn => ({
  connectDropTarget: conn.dropTarget(),
});
