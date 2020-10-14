// @flow
import {
  type DefaultEdge as DefaultEdgeType,
  type DefaultNode as DefaultNodeType,
  SET_DEFAULTS,
} from './types';

export const ACTION_SET_DEFAULTS = (
  edgeBody: DefaultEdgeType,
  nodeBody: DefaultNodeType,
) => ({
  type: SET_DEFAULTS,
  edgeBody,
  nodeBody,
});

export default {
  ACTION_SET_DEFAULTS,
};
