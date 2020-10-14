// @flow
export type IGraphControlProps = {
  maxZoom?: number;
  minZoom?: number;
  zoomLevel: number;
  classes: any;
  zoomToFit: (event: SyntheticMouseEvent<HTMLButtonElement>) => number;
  modifyZoom: (delta: number) => number;
}

export type IGraphControlState = {
  open: boolean;
}
