// @flow
import isEqual from 'lodash.isequal';
import differenceWith from 'lodash.differencewith';

import store from '../../store/store';
import {
  type CounterGridActions as CounterGridActionsType,
  GET_COUNTER_GRID_FAILED,
  GET_COUNTER_GRID_SUCCEEDED,
  GET_COUNTER_GRID_REQUESTED,
  UPDATE_COUNTER_GRID_FAILED,
  UPDATE_COUNTER_GRID_SUCCEEDED,
  UPDATE_COUNTER_GRID_REQUESTED,
  COUNTER_GRID_ACTIONS_CLEAR,
} from './types';

export const ACTION_GET_COUNTER_GRID_FAILED = () => ({
  type: GET_COUNTER_GRID_FAILED,
});

export const ACTION_GET_COUNTER_GRID_SUCCEEDED = (
  counterActions: CounterGridActionsType,
) => ({
  type: GET_COUNTER_GRID_SUCCEEDED,
  counterActions,
});

export const ACTION_GET_COUNTER_GRID_REQUESTED = (mapId: string) => ({
  type: GET_COUNTER_GRID_REQUESTED,
  mapId,
});

export const ACTION_UPDATE_COUNTER_GRID_FAILED = () => ({
  type: UPDATE_COUNTER_GRID_FAILED,
});

export const ACTION_UPDATE_COUNTER_GRID_SUCCEEDED = (
  updatedCounterActions: CounterGridActionsType,
) => ({
  type: UPDATE_COUNTER_GRID_SUCCEEDED,
  updatedCounterActions,
});

export const ACTION_UPDATE_COUNTER_GRID_REQUESTED = (
  mapId: string,
  counterValues: CounterGridActionsType,
) => {
  const { counterGrid: { actions } } = store.getState();
  const counterActions = differenceWith(counterValues, actions, isEqual);

  return {
    type: UPDATE_COUNTER_GRID_REQUESTED,
    mapId,
    counterActions,
    counterValues,
  };
};

export const COUNTER_GRID_ACTION_ACTIONS_CLEAR = () => ({
  type: COUNTER_GRID_ACTIONS_CLEAR,
});
