// @flow
import type { Node as NodeType } from '../Node/types';
import type { Edge as EdgeType } from '../Edge/types';
import type { DefaultEdge as DefaultEdgeType } from '../../../../redux/defaults/types';

export type ITargetPosition = {
  clientX: number,
  clientY: number,
};

export type IViewTransform = {
  x: number,
  y: number,
  k: number,
};

export type INodeMapNode = {
  node: NodeType,
  originalArrIndex: number,
  incomingEdges: Array<EdgeType>,
  outgoingEdges: Array<EdgeType>,
  parents: Array<NodeType>,
  children: Array<NodeType>,
};

export type IGraphViewState = {
  edges: any[],
  edgesMap: any,
  nodes: any[],
  nodesMap: any,
  draggedEdge: any,
  draggingEdge: boolean,
  selectedEdgeObj: any,
  selectedNodeObj: any,
  sourceNode: any,
  focused: boolean,
  svgClicked: boolean,
  documentClicked: boolean,
  isLinkingStarted: boolean,
  isResizingStarted: boolean,
  componentUpToDate: boolean,
  viewTransform?: IViewTransform,
};

export type IGraphViewProps = {
  cursor: string,
  backgroundFillId?: string,
  edges: any[],
  edgeArrowSize?: number,
  edgeTypes: any,
  gridDotSize?: number,
  gridSize?: number,
  gridSpacing?: number,
  layoutEngine?: string,
  maxZoom?: number,
  minZoom?: number,
  nodes: any[],
  readOnly?: boolean,
  selected: NodeType | EdgeType | null,
  focused: NodeType | null,
  zoomDelay?: number,
  zoomDur?: number,
  onCopySelected?: () => void,
  onCreateEdge: (sourceNode: NodeType, targetNode: NodeType) => void,
  onCreateNodeWithEdge: (x: number, y: number, sourceNode: NodeType) => void,
  onDeleteEdge: (edgeId: number) => void,
  onCreateNode: (x: number, y: number) => void,
  onDeleteNode: (node: NodeType) => void,
  onPasteSelected?: () => void,
  onSelectEdge: (selectedEdge: EdgeType | null, x?: number, y?: number) => void,
  onSelectNode: (node: NodeType | null) => void,
  onCollapseNode: (nodeId: number) => void,
  onResizeNode: (nodeId: number, width: number, height: number) => void,
  onLockNode: (nodeId: number) => void,
  onNodeFocused: (nodeId: number, x: number, y: number) => void,
  onUndo?: () => void,
  onRedo?: () => void,
  onUpdateNode: (node: NodeType) => void,
  renderBackground?: (gridSize?: number) => any,
  renderDefs?: () => any,
  afterRenderEdge?: (
    id: string,
    element: any,
    edge: EdgeType,
    edgeContainer: any,
    isEdgeSelected: boolean,
  ) => void,
  ACTION_SET_CURSOR: Function,
  edgeDefaults: DefaultEdgeType,
};
