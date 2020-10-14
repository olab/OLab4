// @flow
import {
  type DefaultsActions,
  type Defaults as DefaultsType,
  SET_DEFAULTS,
} from './types';

export const initialDefaultsState: DefaultsType = {
  edgeBody: {
    label: '',
    color: '',
    variant: null,
    thickness: null,
    linkStyle: null,
    isHidden: null,
    isFollowOnce: null,
  },
  nodeBody: {
    title: '',
    text: '',
    x: null,
    y: null,
    isLocked: null,
    isCollapsed: null,
    height: null,
    width: null,
    linkStyle: null,
    linkType: null,
    type: null,
    color: '',
  },
};

const defaults = (state: DefaultsType = initialDefaultsState, action: DefaultsActions) => {
  switch (action.type) {
    case SET_DEFAULTS: {
      const { edgeBody, nodeBody } = action;

      return {
        edgeBody: {
          ...state.edgeBody,
          ...edgeBody,
        },
        nodeBody: {
          ...state.nodeBody,
          ...nodeBody,
        },
      };
    }
    default:
      return state;
  }
};

export default defaults;
