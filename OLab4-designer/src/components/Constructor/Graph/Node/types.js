// @flow
export type IPoint = {
  x: number,
  y: number,
};

export type Node = {
  id: number,
  mapId: number,
  title: string,
  type: number,
  ...IPoint,
  width: number,
  height: number,
  color: string,
  text: string,
  info: string,
  annotation: string,
  linkStyle: number,
  linkType: number,
  priorityId: number,
  isCollapsed: boolean,
  isLocked: boolean,
  isVisitOnce: boolean,
  isSelected: boolean,
  isFocused: boolean,
  isEnd: boolean,
};

export type INodeProps = {
  data: Node,
  id: string,
  isSelected: boolean,
  isResizingStarted: boolean,
  isLinkingStarted: boolean,
  isLinkSource: boolean,
  onNodeMove: (point: IPoint, id: number) => void,
  onNodeSelected: (data: any) => void,
  onNodeUpdate: (point: IPoint, nodeId: number) => void,
  onCreateNodeWithEdge: (x: number, y: number, sourceNode: Node) => void,
  onNodeCollapsed: (id: number) => void,
  onNodeResizeEnded: (id: number, width: number, height: number) => void,
  onNodeResizeStarted: () => void,
  onNodeLocked: (id: number) => void,
  onNodeLink: (data: any) => void,
  onNodeFocused: (id: number) => void,
  layoutEngine?: any,
  viewWrapperElem: HTMLDivElement,
};

export type INodeState = {
  ...IPoint,
  prevX: number,
  prevY: number,
  isResizeStart: boolean,
};
