// @flow
export type IUndoRedoButtonsProps = {
  classes: any;
  isUndoAvailable: boolean;
  isRedoAvailable: boolean;
  ACTION_REDO_MAP: () => void;
  ACTION_UNDO_MAP: () => void;
};
