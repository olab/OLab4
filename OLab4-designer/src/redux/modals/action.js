// @flow
import store from '../../store/store';
import { UPDATE_MODAL } from './types';

import { MODALS_NAMES } from '../../components/Modals/config';

export const ACTION_CLOSE_MODAL = (modalName: string) => {
  const { modals } = store.getState();
  const modalValue = { ...modals[modalName] };
  modalValue.isShow = false;

  return {
    type: UPDATE_MODAL,
    modalName,
    modalValue,
  };
};

export const ACTION_TOGGLE_MODAL = (modalName: string) => {
  const { modals } = store.getState();
  const modalValue = { ...modals[modalName] };
  modalValue.isShow = !modalValue.isShow;

  return {
    type: UPDATE_MODAL,
    modalName,
    modalValue,
  };
};

export const ACTION_ADJUST_POSITION_MODAL = (
  modalName: string,
  offsetX: number,
  offsetY: number,
) => {
  const { modals } = store.getState();
  const modalValue = { ...modals[modalName] };

  if (modalName === MODALS_NAMES.NODE_EDITOR_MODAL) {
    modalValue.x -= offsetX;
    modalValue.y -= offsetY;
  } else {
    modalValue.x += offsetX;
    modalValue.y += offsetY;
  }

  return {
    type: UPDATE_MODAL,
    modalName,
    modalValue,
  };
};

export const ACTION_SET_POSITION_MODAL = (
  modalName: string,
  x: number,
  y: number,
) => {
  const { modals } = store.getState();
  const modalValue = { ...modals[modalName] };

  modalValue.x = x;
  modalValue.y = y;

  return {
    type: UPDATE_MODAL,
    modalName,
    modalValue,
  };
};
