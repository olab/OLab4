// @flow
import { MapItem } from '../../redux/map/types';

export const SAVE_MAP_TO_UNDO = 'SAVE_MAP_TO_UNDO';
type MapToUndo = {
  type: 'SAVE_MAP_TO_UNDO',
  currentMap: MapItem,
};

export type UndoRedoActions = MapToUndo;

export default {
  SAVE_MAP_TO_UNDO,
};
