import { ACTION_SAVE_MAP_TO_UNDO } from './action';

import {
  CREATE_NODE,
  UPDATE_NODE,
  DELETE_NODE_REQUESTED,
  CREATE_EDGE,
  CREATE_NODE_WITH_EDGE,
  UPDATE_EDGE,
  DELETE_EDGE,
} from '../../redux/map/types';

const undoRedo = store => next => (action) => {
  if ([
    CREATE_NODE,
    UPDATE_NODE,
    DELETE_NODE_REQUESTED,
    CREATE_NODE_WITH_EDGE,
    CREATE_EDGE,
    UPDATE_EDGE,
    DELETE_EDGE,
  ].includes(action.type)) {
    store.dispatch(ACTION_SAVE_MAP_TO_UNDO());
  }

  next(action);
};

export default undoRedo;
