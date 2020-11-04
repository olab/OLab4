// @flow
import {
  SCOPED_OBJECT_CREATE_FAILED,
  SCOPED_OBJECT_CREATE_REQUESTED,
  SCOPED_OBJECT_CREATE_SUCCEEDED,
  SCOPED_OBJECT_DELETE_FAILED,
  SCOPED_OBJECT_DELETE_SUCCEEDED,
  SCOPED_OBJECT_DETAILS_FULFILLED,
  SCOPED_OBJECT_DETAILS_REQUESTED,
  SCOPED_OBJECT_UPDATE_FULFILLED,
  SCOPED_OBJECT_UPDATE_REQUESTED,
  // type QuestionResponse as ScopedObjectType,
  type ScopedObjectsState as ScopedObjectsType,
} from './types';

export const initialScopedObjectsState: ScopedObjectsType = {
  isCreating: false,
  isDeleting: false,
  isFetching: false,
  isUpdating: false,
  questionresponses: [],
};

const questionResponses = (
  state: ScopedObjectsType = initialScopedObjectsState,
  action: ScopedObjectsActions,
) => {
  switch (action.type) {
    // case SCOPED_OBJECTS_REQUEST_SUCCEEDED: {
    //   const { scopedObjectsData } = action;

    //   return {
    //     ...scopedObjectsData,
    //     isFetching: false,
    //   };
    // }
    // case SCOPED_OBJECTS_REQUESTED:
    // case SCOPED_OBJECTS_TYPED_REQUESTED:
    //   return {
    //     ...state,
    //     isFetching: true,
    //   };
    // case SCOPED_OBJECTS_REQUEST_FAILED:
    // case SCOPED_OBJECTS_TYPED_FAILED:
    //   return {
    //     ...state,
    //     isFetching: false,
    //   };
    case SCOPED_OBJECT_DETAILS_FULFILLED:
    case SCOPED_OBJECT_DETAILS_REQUESTED: {
      const { scopedObjectType, scopedObjectIndex, scopedObject } = action;

      const response = {
        ...state,
        [scopedObjectType]: [
          ...state[scopedObjectType].slice(0, scopedObjectIndex),
          scopedObject,
          ...state[scopedObjectType].slice(scopedObjectIndex + 1),
        ],
        isFetching: false,
      };

      return response;
    }
    case SCOPED_OBJECT_CREATE_REQUESTED: {
      const response = {
        ...state,
        isCreating: true,
      };

      return response;
    }
    case SCOPED_OBJECT_CREATE_SUCCEEDED: {
      const { scopedObjectId, scopedObjectType, scopedObjectData } = action;

      const response = {
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

      return response;
    }
    case SCOPED_OBJECT_CREATE_FAILED: {
      const response = {
        ...state,
        isCreating: false,
      };

      return response;
    }
    case SCOPED_OBJECT_UPDATE_REQUESTED: {
      const response = {
        ...state,
        isUpdating: true,
      };

      return response;
    }
    case SCOPED_OBJECT_UPDATE_FULFILLED: {
      const response = {
        ...state,
        isUpdating: false,
      };

      return response;
    }
    // case SCOPED_OBJECTS_TYPED_SUCCEEDED: {
    //   const { scopedObjectType, scopedObjects: scopedObjectsTyped } = action;

    //   return {
    //     ...state,
    //     [scopedObjectType]: [
    //       ...scopedObjectsTyped,
    //     ],
    //     isFetching: false,
    //   };
    // }
    // case SCOPED_OBJECT_DELETE_REQUESTED:
    //   return {
    //     ...state,
    //     isDeleting: true,
    //   };
    case SCOPED_OBJECT_DELETE_FAILED: {
      const response = {
        ...state,
        isDeleting: false,
      };

      return response;
    }
    case SCOPED_OBJECT_DELETE_SUCCEEDED: {
      const { scopedObjectType, scopedObjectIndex } = action;

      const response = {
        ...state,
        [scopedObjectType]: [
          ...state[scopedObjectType].slice(0, scopedObjectIndex),
          ...state[scopedObjectType].slice(scopedObjectIndex + 1),
        ],
        isDeleting: false,
      };

      return response;
    }
    // case SCOPED_OBJECTS_CLEAR:
    //   return {
    //     ...initialScopedObjectsState,
    //   };
    default:
      return state;
  }
};

export default questionResponses;
