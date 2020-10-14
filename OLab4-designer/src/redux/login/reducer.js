// @flow
import {
  type UserActions,
  type User as UserType,
  USER_AUTH_FAILED,
  USER_AUTH_SUCCEEDED,
  USER_AUTH_REQUESTED,
  USER_AUTH_LOGOUT,
} from './types';

export const initialUserState: UserType = {
  data: {
    id: null,
    name: '',
    username: '',
  },
  authData: {
    token: '',
  },
  isAuth: false,
  isFetching: false,
};

const user = (state: UserType = initialUserState, action: UserActions) => {
  switch (action.type) {
    case USER_AUTH_REQUESTED:
      return {
        ...state,
        isFetching: true,
      };

    case USER_AUTH_SUCCEEDED: {
      const { token } = action;

      return {
        ...state,
        isAuth: true,
        isFetching: false,
        authData: {
          token,
        },
      };
    }
    case USER_AUTH_FAILED:
      return {
        ...state,
        isAuth: false,
        isFetching: false,
      };
    case USER_AUTH_LOGOUT:
      return {
        ...state,
        isAuth: false,
        authData: {
          token: '',
        },
      };
    default:
      return state;
  }
};

export default user;
