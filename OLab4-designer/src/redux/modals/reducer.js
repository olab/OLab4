// @flow
import {
  type ModalsActions,
  UPDATE_MODAL,
} from './types';
import type { Modals as ModalsType } from '../../components/Modals/types';

import {
  MODALS_NAMES,
  PICKER_OFFSET_Y,
  PICKER_OFFSET_X,
  NODE_EDITOR_OFFSET_X,
  NODE_EDITOR_OFFSET_Y,
} from '../../components/Modals/config';

export const initialModalsState: ModalsType = {
  [MODALS_NAMES.SO_PICKER_MODAL]: {
    isShow: false,
    x: PICKER_OFFSET_X,
    y: PICKER_OFFSET_Y,
  },
  [MODALS_NAMES.NODE_EDITOR_MODAL]: {
    x: NODE_EDITOR_OFFSET_X,
    y: NODE_EDITOR_OFFSET_Y,
  },
  [MODALS_NAMES.LINK_EDITOR_MODAL]: {
    x: 0,
    y: 0,
  },
};

const modals = (state: ModalsType = initialModalsState, action: ModalsActions) => {
  switch (action.type) {
    case UPDATE_MODAL: {
      const { modalName, modalValue } = action;

      return {
        ...state,
        [modalName]: modalValue,
      };
    }
    default:
      return state;
  }
};

export default modals;
