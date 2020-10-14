// @flow
import {
  USER_AUTH_REQUESTED, USER_AUTH_LOGOUT, USER_AUTH_SUCCEEDED, USER_AUTH_FAILED,
} from './types';
import type { UserLoginData } from '../../components/Login/types';

export const ACTION_USER_AUTH_REQUESTED = (userLoginData: UserLoginData) => ({
  type: USER_AUTH_REQUESTED,
  userLoginData,
});

export const ACTION_USER_AUTH_LOGOUT = () => ({
  type: USER_AUTH_LOGOUT,
});

export const ACTION_USER_AUTH_SUCCEEDED = (token: string) => ({
  type: USER_AUTH_SUCCEEDED,
  token,
});

export const ACTION_USER_AUTH_FAILED = () => ({
  type: USER_AUTH_FAILED,
});
