// @flow
import {
  type CounterActions as CounterActionsType,
  type CounterGridActions as CounterGridActionsType,
  GET_COUNTER_GRID_REQUESTED,
  GET_COUNTER_GRID_SUCCEEDED,
  GET_COUNTER_GRID_FAILED,
  UPDATE_COUNTER_GRID_REQUESTED,
  UPDATE_COUNTER_GRID_SUCCEEDED,
  UPDATE_COUNTER_GRID_FAILED,
  COUNTER_GRID_ACTIONS_CLEAR,
} from './types';

export const initialCounterGridState: CounterActionsType = {
  nodes: [],
  counters: [],
  actions: [],
  isFetching: false,
};

const counterGrid = (
  state: CounterActionsType = initialCounterGridState,
  action: CounterGridActionsType,
) => {
  switch (action.type) {
    case GET_COUNTER_GRID_REQUESTED:
    case UPDATE_COUNTER_GRID_REQUESTED:
      return {
        ...state,
        isFetching: true,
      };
    case GET_COUNTER_GRID_FAILED:
    case UPDATE_COUNTER_GRID_FAILED:
      return {
        ...state,
        isFetching: false,
      };
    case UPDATE_COUNTER_GRID_SUCCEEDED: {
      const { updatedCounterActions: actions } = action;

      return {
        ...state,
        actions,
        isFetching: false,
      };
    }
    case GET_COUNTER_GRID_SUCCEEDED: {
      const { counterActions } = action;

      return {
        ...counterActions,
        isFetching: false,
      };
    }
    case COUNTER_GRID_ACTIONS_CLEAR:
      return {
        ...initialCounterGridState,
      };
    default:
      return state;
  }
};

export default counterGrid;
