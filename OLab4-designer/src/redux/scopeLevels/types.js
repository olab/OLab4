// @flow
export type ScopeLevel = {
  id: number,
  name: string,
  description: string,
  url: string,
};

export type ScopeLevels = {
  [type: string]: Array<ScopeLevel>,
  isFetching: boolean,
};

const SCOPE_LEVELS_REQUESTED = 'SCOPE_LEVELS_REQUESTED';
type ScopeLevelsRequested = {
  type: 'SCOPE_LEVELS_REQUESTED',
  level: string,
};

const SCOPE_LEVELS_REQUEST_FAILED = 'SCOPE_LEVELS_REQUEST_FAILED';
type ScopeLevelsRequestFailed = {
  type: 'SCOPE_LEVELS_REQUEST_FAILED',
};

const SCOPE_LEVELS_REQUEST_SUCCEEDED = 'SCOPE_LEVELS_REQUEST_SUCCEEDED';
type ScopeLevelsRequestSucceeded = {
  type: 'SCOPE_LEVELS_REQUEST_SUCCEEDED',
  level: string,
  levelList: Array<ScopeLevel>,
};

const SCOPE_LEVELS_CLEAR = 'SCOPE_LEVELS_CLEAR';
type ScopeLevelsClear = {
  type: 'SCOPE_LEVELS_CLEAR',
};

export type ScopeLevelsActions = ScopeLevelsClear |
  ScopeLevelsRequested | ScopeLevelsRequestFailed |
  ScopeLevelsRequestSucceeded;

export {
  SCOPE_LEVELS_REQUESTED,
  SCOPE_LEVELS_REQUEST_FAILED,
  SCOPE_LEVELS_REQUEST_SUCCEEDED,
  SCOPE_LEVELS_CLEAR,
};
