// @flow
import type { Edge as EdgeType } from '../../Constructor/Graph/Edge/types';
import type { ModalPosition as ModalPositionType } from '../types';

export type ILinkEditorProps = {
  ...ModalPositionType,
  link: EdgeType,
  links: Array<EdgeType>,
  isDragging: boolean;
  connectDragSource: Function;
  connectDragPreview: Function;
  ACTION_UPDATE_EDGE: Function;
  ACTION_DESELECT_EDGE: Function;
  ACTION_ADJUST_POSITION_MODAL: Function;
  layoutEngine: string,
};

export type ILinkEditorState = EdgeType;
