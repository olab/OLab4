// @flow
export type DefaultEdge = {
  label: string,
  color: string,
  variant: number | null,
  thickness: number | null,
  linkStyle: number | null,
  isHidden: boolean | null,
  isFollowOnce: boolean | null,
};

export type DefaultNode = {
  title: string,
  text: string,
  x: number | null,
  y: number | null,
  height: number | null,
  width: number | null,
  type: number | null,
  color: string,
  linkType: number | null,
  linkStyle: number | null,
  isLocked: boolean | null,
  isCollapsed: boolean | null,
};

export type Defaults = {
  edgeBody: DefaultEdge,
  nodeBody: DefaultNode,
};

export const SET_DEFAULTS = 'SET_DEFAULTS';
type SetDefaults = {
  type: 'SET_DEFAULTS',
  edgeBody: DefaultEdge,
  nodeBody: DefaultNode,
};

export type DefaultsActions = SetDefaults;

export default {
  SET_DEFAULTS,
};
