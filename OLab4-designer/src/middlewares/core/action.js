// @flow
import cloneDeep from 'lodash.clonedeep';
import store from '../../store/store';

import { SAVE_MAP_TO_UNDO } from './types';

export const ACTION_SAVE_MAP_TO_UNDO = () => {
  const { map: { nodes, edges } } = store.getState();
  const currentMap = cloneDeep({ nodes, edges });

  return {
    type: SAVE_MAP_TO_UNDO,
    currentMap,
  };
};

export default {
  ACTION_SAVE_MAP_TO_UNDO,
};
