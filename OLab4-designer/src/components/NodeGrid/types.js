// @flow
import type { Node as FullNode } from '../Constructor/Graph/Node/types';

export type Node = {
  id: number,
  x: number,
  y: number,
  text: string,
  title: string,
};

export type NodeGridProps = {
  classes: {
    [props: string]: any,
  },
  mapId: string,
  mapIdUrl: number,
  nodes: Array<FullNode>,
  isFetching: boolean,
  ACTION_GET_WHOLE_MAP_MIDDLEWARE: Function,
  ACTION_UPDATE_NODE_GRID_REQUESTED: Function,
};

export type NodeGridState = {
  nodes: Array<Node>,
};
