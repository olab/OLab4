// @flow
import {
  RESPONSE_CREATE_FAILED,
  RESPONSE_CREATE_REQUESTED,
  RESPONSE_CREATE_SUCCEEDED,
  RESPONSE_DELETE_FAILED,
  RESPONSE_DELETE_SUCCEEDED,
  // SCOPED_OBJECT_DETAILS_FULFILLED,
  // SCOPED_OBJECT_DETAILS_REQUESTED,
  // SCOPED_OBJECT_UPDATE_FULFILLED,
  // SCOPED_OBJECT_UPDATE_REQUESTED,
  // type QuestionResponse as ScopedObjectType,
  type ScopedObjectsState as ScopedObjectsType,
} from './types';
import { initialScopedObjectsState } from '../scopedObjects/reducer';

const questionResponses = (
  state: ScopedObjectsType = initialScopedObjectsState,
  action: ScopedObjectsActions,
) => {
  switch (action.type) {
    case RESPONSE_CREATE_REQUESTED:
      return {
        ...state,
        isCreating: true,
      };
    case RESPONSE_CREATE_SUCCEEDED: {
      // const { scopedObjectId, scopedObjectData } = action;

      return {
        ...state,
        isCreating: false,
      };
    }
    case RESPONSE_CREATE_FAILED:
      return {
        ...state,
        isCreating: false,
      };

    case RESPONSE_DELETE_FAILED: {
      const response = {
        ...state,
        isDeleting: false,
      };

      return response;
    }

    case RESPONSE_DELETE_SUCCEEDED: {
      const {
        scopedObjectType,
        scopedObjectIndex,
        scopedObject,
      } = action;

      scopedObject.responses.splice(scopedObjectIndex, 1);

      const response = {
        ...state,
        [scopedObjectType]: [
          scopedObject,
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
