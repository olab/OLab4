// @flow
import React from 'react';
import {
  createNotification,
  NOTIFICATION_TYPE_SUCCESS,
  NOTIFICATION_TYPE_WARNING,
  NOTIFICATION_TYPE_ERROR,
  NOTIFICATION_TYPE_INFO,
} from 'react-redux-notify';
import {
  Info as InfoIcon,
  Error as ErrorIcon,
  Warning as WarningIcon,
  CheckCircle as SuccessIcon,
} from '@material-ui/icons';

import { DEFAULTS } from './config';

export const ACTION_NOTIFICATION_SUCCESS = (message: string) => createNotification({
  message,
  type: NOTIFICATION_TYPE_SUCCESS,
  icon: <SuccessIcon />,
  ...DEFAULTS,
});

export const ACTION_NOTIFICATION_WARNING = (message: string) => createNotification({
  message,
  type: NOTIFICATION_TYPE_WARNING,
  icon: <WarningIcon />,
  ...DEFAULTS,
});

export const ACTION_NOTIFICATION_ERROR = (message: string) => createNotification({
  message,
  type: NOTIFICATION_TYPE_ERROR,
  icon: <ErrorIcon />,
  ...DEFAULTS,
});

export const ACTION_NOTIFICATION_INFO = (message: string) => createNotification({
  message,
  type: NOTIFICATION_TYPE_INFO,
  icon: <InfoIcon />,
  ...DEFAULTS,
});
