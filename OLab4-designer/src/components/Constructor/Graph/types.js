// @flow
import type { LayoutEngine as LayoutEngineType } from './utilities/layout-engine/layout-engine-config';
import type { Node as NodeType } from './Node/types';
import type { Edge as EdgeType } from './Edge/types';
import type { Defaults as DefaultsType } from '../../../redux/defaults/types';
import type { Map as MapType } from '../../../redux/map/types';

export type IGraphProps = {
  isFullScreen: boolean,
  minZoom: number,
  maxZoom: number,
  map: MapType,
  isUndoAvailable: boolean,
  isRedoAvailable: boolean,
  layoutEngine: string,
  connectDropTarget: Function,
  ACTION_SET_POSITION_MODAL: (modalName: string, x: number, y: number) => void,
  ACTION_UNDO_MAP: () => void,
  ACTION_REDO_MAP: () => void,
  ACTION_SELECT_NODE: (nodeId: number | null) => void,
  ACTION_CREATE_NODE: (node: NodeType) => void,
  ACTION_CREATE_NODE_WITH_EDGE: (node: NodeType, edge: EdgeType, sourceNodeId: number) => void,
  ACTION_UPDATE_NODE: (node: NodeType) => void,
  ACTION_DELETE_NODE_REQUESTED: (nodeId: number) => void,
  ACTION_DELETE_NODE_MIDDLEWARE: (mapId: number, nodeId: number, nodeType: number) => void,
  ACTION_SELECT_EDGE: (edgeId: number | null) => void,
  ACTION_DELETE_EDGE: (edgeId: string | number, nodeId: string | number) => void,
  ACTION_CREATE_EDGE: (edge: EdgeType) => void,
  ACTION_UPDATE_NODE_COLLAPSE: (nodeId: number) => void,
  ACTION_UPDATE_NODE_RESIZE: (nodeId: number, width: number, height: number) => void,
  ACTION_UPDATE_NODE_LOCK: (nodeId: number) => void,
  ACTION_FOCUS_NODE: (nodeId: number) => void,
  ACTION_NOTIFICATION_INFO: (message: string) => void,
  defaults: DefaultsType,
};

export type IGraphState = {
  layoutEngine: LayoutEngineType;
  copiedNode: NodeType;
};
