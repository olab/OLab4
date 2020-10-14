// @flow
import {
  SET_CURSOR,
  SET_FULLSCREEN,
  SET_LAYOUT_ENGINE,
  TOGGLE_FULLSCREEN,
} from './types';

export const ACTION_SET_CURSOR = (cursor: string) => ({
  type: SET_CURSOR,
  cursor,
});

export const ACTION_SET_FULLSCREEN = (isFullScreen: boolean) => ({
  type: SET_FULLSCREEN,
  isFullScreen,
});

export const ACTION_SET_LAYOUT_ENGINE = (layoutEngine: string) => ({
  type: SET_LAYOUT_ENGINE,
  layoutEngine,
});

export const ACTION_TOGGLE_FULLSCREEN = () => ({
  type: TOGGLE_FULLSCREEN,
});
