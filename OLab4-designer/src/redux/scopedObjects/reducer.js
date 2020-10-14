// @flow
import {
  type ScopedObjectsActions,
  type ScopedObjectsState as ScopedObjectsType,
  SCOPED_OBJECTS_REQUEST_FAILED,
  SCOPED_OBJECTS_REQUEST_SUCCEEDED,
  SCOPED_OBJECTS_REQUESTED,
  SCOPED_OBJECTS_TYPED_REQUESTED,
  SCOPED_OBJECTS_TYPED_SUCCEEDED,
  SCOPED_OBJECTS_TYPED_FAILED,
  SCOPED_OBJECTS_CLEAR,
  SCOPED_OBJECT_DETAILS_FULFILLED,
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_CREATE_SUCCEEDED,
  SCOPED_OBJECT_CREATE_FAILED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  SCOPED_OBJECT_UPDATE_FULFILLED,
  SCOPED_OBJECT_DELETE_REQUESTED,
  SCOPED_OBJECT_DELETE_SUCCEEDED,
  SCOPED_OBJECT_DELETE_FAILED,
} from './types';

export const initialScopedObjectsState: ScopedObjectsType = {
  questions: [],
  constants: [],
  counters: [],
  files: [],
  isFetching: false,
  isCreating: false,
  isUpdating: false,
  isDeleting: false,
};

const scopedObjects = (
  state: ScopedObjectsType = initialScopedObjectsState,
  action: ScopedObjectsActions,
) => {
  switch (action.type) {
    case SCOPED_OBJECTS_REQUEST_SUCCEEDED: {
      const { scopedObjectsData } = action;

      return {
        ...scopedObjectsData,
        isFetching: false,
      };
    }
    case SCOPED_OBJECTS_REQUESTED:
    case SCOPED_OBJECTS_TYPED_REQUESTED:
      return {
        ...state,
        isFetching: true,
      };
    case SCOPED_OBJECTS_REQUEST_FAILED:
    case SCOPED_OBJECTS_TYPED_FAILED:
      return {
        ...state,
        isFetching: false,
      };
    case SCOPED_OBJECT_DETAILS_FULFILLED:
    case SCOPED_OBJECT_DETAILS_REQUESTED: {
      const { scopedObjectType, scopedObjectIndex, scopedObject } = action;

      return {
        ...state,
        [scopedObjectType]: [
          ...state[scopedObjectType].slice(0, scopedObjectIndex),
          scopedObject,
          ...state[scopedObjectType].slice(scopedObjectIndex + 1),
        ],
        isFetching: false,
      };
    }
    case SCOPED_OBJECT_CREATE_REQUESTED:
      return {
        ...state,
        isCreating: true,
      };
    case SCOPED_OBJECT_CREATE_SUCCEEDED: {
      const { scopedObjectId, scopedObjectType, scopedObjectData } = action;

      return {
        ...state,
        [scopedObjectType]: [
          ...state[scopedObjectType],
          {
            ...scopedObjectData,
            id: scopedObjectId,
          },
        ],
        isCreating: false,
      };
    }
    case SCOPED_OBJECT_CREATE_FAILED:
      return {
        ...state,
        isCreating: false,
      };
    case SCOPED_OBJECT_UPDATE_REQUESTED:
      return {
        ...state,
        isUpdating: true,
      };
    case SCOPED_OBJECT_UPDATE_FULFILLED:
      return {
        ...state,
        isUpdating: false,
      };
    case SCOPED_OBJECTS_TYPED_SUCCEEDED: {
      const { scopedObjectType, scopedObjects: scopedObjectsTyped } = action;

      return {
        ...state,
        [scopedObjectType]: [
          ...scopedObjectsTyped,
        ],
        isFetching: false,
      };
    }
    case SCOPED_OBJECT_DELETE_REQUESTED:
      return {
        ...state,
        isDeleting: true,
      };
    case SCOPED_OBJECT_DELETE_FAILED:
      return {
        ...state,
        isDeleting: false,
      };
    case SCOPED_OBJECT_DELETE_SUCCEEDED: {
      const { scopedObjectType, scopedObjectIndex } = action;

      return {
        ...state,
        [scopedObjectType]: [
          ...state[scopedObjectType].slice(0, scopedObjectIndex),
          ...state[scopedObjectType].slice(scopedObjectIndex + 1),
        ],
        isDeleting: false,
      };
    }
    case SCOPED_OBJECTS_CLEAR:
      return {
        ...initialScopedObjectsState,
      };
    default:
      return state;
  }
};

export default scopedObjects;
