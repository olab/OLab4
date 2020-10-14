// @flow
export type edgeTypes = {
  shape: any,
  shapeId: string,
}

export type IDefsProps = {
  gridSpacing?: number;
  gridDotSize?: number;
  edgeArrowSize: number;
  edgeTypes?: any;
  renderDefs?: () => any | null;
};
export type IDefsState = {
  graphConfigDefs: any;
};
