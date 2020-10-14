// @flow
export type Counter = {
  id: number,
  name: string,
  expression: string,
  description: string,
  counterId: number,
  isVisible: boolean,
};

export type CounterGridNode = {
  ...Counter,
  nodeId: number,
  title: string,
};

export type CounterActions = {
  nodes: Array<CounterGridNode>,
  counters: Array<Counter>,
  actions: Array<Counter>,
};

const GET_COUNTER_GRID_SUCCEEDED = 'GET_COUNTER_GRID_SUCCEEDED';
type GetCounterGridSucceeded = {
  type: 'GET_COUNTER_GRID_SUCCEEDED',
  counterActions: CounterActions,
};

const GET_COUNTER_GRID_FAILED = 'GET_COUNTER_GRID_FAILED';
type GetCounterGridFailed = {
  type: 'GET_COUNTER_GRID_FAILED',
};

const GET_COUNTER_GRID_REQUESTED = 'GET_COUNTER_GRID_REQUESTED';
type GetCounterGridRequested = {
  type: 'GET_COUNTER_GRID_REQUESTED',
  mapId: string,
};

const UPDATE_COUNTER_GRID_SUCCEEDED = 'UPDATE_COUNTER_GRID_SUCCEEDED';
type UpdateCounterGridSucceeded = {
  type: 'UPDATE_COUNTER_GRID_SUCCEEDED',
  updatedCounterActions: Array<Counter>,
};

const UPDATE_COUNTER_GRID_FAILED = 'UPDATE_COUNTER_GRID_FAILED';
type UpdateCounterGridFailed = {
  type: 'UPDATE_COUNTER_GRID_FAILED',
};

const UPDATE_COUNTER_GRID_REQUESTED = 'UPDATE_COUNTER_GRID_REQUESTED';
type UpdateCounterGridRequested = {
  type: 'UPDATE_COUNTER_GRID_REQUESTED',
  mapId: string,
  counterActions: CounterActions,
  counterValues: CounterActions,
};

const COUNTER_GRID_ACTIONS_CLEAR = 'COUNTER_GRID_ACTIONS_CLEAR';
type ActionsClear = {
  type: 'COUNTER_GRID_ACTIONS_CLEAR',
};

export type CounterGridActions = GetCounterGridSucceeded | GetCounterGridFailed |
  GetCounterGridRequested | UpdateCounterGridSucceeded | UpdateCounterGridFailed |
  UpdateCounterGridRequested | ActionsClear;

export {
  GET_COUNTER_GRID_REQUESTED,
  GET_COUNTER_GRID_SUCCEEDED,
  GET_COUNTER_GRID_FAILED,
  UPDATE_COUNTER_GRID_REQUESTED,
  UPDATE_COUNTER_GRID_SUCCEEDED,
  UPDATE_COUNTER_GRID_FAILED,
  COUNTER_GRID_ACTIONS_CLEAR,
};
