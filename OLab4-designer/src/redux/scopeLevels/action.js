// @flow
import {
  type ScopeLevel as ScopeLevelType,
  SCOPE_LEVELS_REQUESTED,
  SCOPE_LEVELS_REQUEST_FAILED,
  SCOPE_LEVELS_REQUEST_SUCCEEDED,
  SCOPE_LEVELS_CLEAR,
} from './types';

export const ACTION_SCOPE_LEVELS_REQUESTED = (level: string) => ({
  type: SCOPE_LEVELS_REQUESTED,
  level,
});

export const ACTION_SCOPE_LEVELS_REQUEST_FAILED = () => ({
  type: SCOPE_LEVELS_REQUEST_FAILED,
});

export const ACTION_SCOPE_LEVELS_REQUEST_SUCCEEDED = (
  level: string,
  levelList: Array<ScopeLevelType>,
) => ({
  type: SCOPE_LEVELS_REQUEST_SUCCEEDED,
  level,
  levelList,
});

export const ACTION_SCOPE_LEVELS_CLEAR = () => ({
  type: SCOPE_LEVELS_CLEAR,
});
