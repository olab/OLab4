// @flow
import type { Node as NodeType } from '../Node/types';
import type { DefaultEdge as DefaultEdgeType } from '../../../../redux/defaults/types';

export type Edge = {
  id: number,
  label: string,
  color: string,
  variant: number,
  thickness: number,
  linkStyle: number,
  source: number,
  target: number,
  isHidden: boolean,
  isFollowOnce: boolean,
  isSelected: boolean,
};

export type ITargetPosition = {
  x: number,
  y: number,
};

export type IEdgeProps = {
  data: Edge,
  edgeTypes: any,
  sourceNode: NodeType | null,
  targetNode: NodeType | ITargetPosition,
  isLinkingStarted: boolean,
  isSelected: boolean,
  hasSibling: boolean,
  viewWrapperElem: HTMLDivElement,
  edgeDefaults: DefaultEdgeType,
};

export type Intersect = {
  type: string,
  point: ITargetPosition,
};

export type IntersectResponse = {
  xOff: number,
  yOff: number,
  intersect: Intersect,
};
