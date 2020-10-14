// @flow
import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { withFormik, Form } from 'formik';
import { withStyles } from '@material-ui/core/styles';
import {
  Button, FormControl, Input,
  InputLabel, Paper, Typography,
} from '@material-ui/core';

import * as authActions from '../../redux/login/action';

import type { UserLoginData, ILoginProps, PropsAuthAction } from './types';

import styles from './styles';

const Login = ({
  classes, isAuth, values, handleChange, history,
}: ILoginProps) => {
  if (isAuth) {
    history.goBack();

    return null;
  }

  return (
    <main className={classes.main}>
      <Paper className={classes.paper}>
        <Typography component="h1" variant="h5">
          Sign in
        </Typography>
        <Form>
          <FormControl margin="normal" required fullWidth>
            <InputLabel htmlFor="username">Username</InputLabel>
            <Input
              name="username"
              type="username"
              value={values.username}
              onChange={handleChange}
            />
          </FormControl>
          <FormControl margin="normal" required fullWidth>
            <InputLabel htmlFor="password">Password</InputLabel>
            <Input
              name="password"
              type="password"
              autoComplete="current-password"
              value={values.password}
              onChange={handleChange}
            />
          </FormControl>
          <Button
            type="submit"
            fullWidth
            variant="contained"
            color="primary"
            className={classes.submit}
          >
            Sign in
          </Button>
        </Form>
      </Paper>
    </main>
  );
};

const loginFormikWrapper = withFormik({
  mapPropsToValues: ({ username = '', password = '' }: UserLoginData) => ({
    username,
    password,
  }),
  handleSubmit: (
    values: UserLoginData, {
      props: { ACTION_USER_AUTH_REQUESTED },
    }: PropsAuthAction,
  ) => {
    ACTION_USER_AUTH_REQUESTED(values);
  },
})(Login);

const mapDispatchToProps = dispatch => ({
  ACTION_USER_AUTH_REQUESTED: (userLoginData: UserLoginData) => {
    dispatch(authActions.ACTION_USER_AUTH_REQUESTED(userLoginData));
  },
});

const mapStateToProps = ({ user: { isAuth, isFetching } }) => ({
  isAuth,
  isFetching,
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  withStyles(styles)(
    withRouter(loginFormikWrapper),
  ),
);
