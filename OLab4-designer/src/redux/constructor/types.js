// @flow
const SET_CURSOR = 'SET_CURSOR';
type SetCursor = {
  type: 'SET_CURSOR',
  cursor: string,
};

const SET_FULLSCREEN = 'SET_FULLSCREEN';
type SetFullscreen = {
  type: 'SET_FULLSCREEN',
  isFullScreen: boolean,
};

const SET_LAYOUT_ENGINE = 'SET_LAYOUT_ENGINE';
type SetLayoutEngine = {
  type: 'SET_LAYOUT_ENGINE',
  layoutEngine: string,
};

const TOGGLE_FULLSCREEN = 'TOGGLE_FULLSCREEN';
type ToggleFullscreen = {
  type: 'TOGGLE_FULLSCREEN',
};

export type ConstructorActions = SetFullscreen |
  ToggleFullscreen | SetCursor |
  SetLayoutEngine;

export {
  SET_CURSOR,
  SET_FULLSCREEN,
  SET_LAYOUT_ENGINE,
  TOGGLE_FULLSCREEN,
};
