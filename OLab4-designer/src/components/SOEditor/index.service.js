// @flow
import { PureComponent } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';

import type { IScopedObjectProps, IScopedObjectState, Icons } from './types';
import type { ScopeLevel as ScopeLevelType } from '../../redux/scopeLevels/types';
import type { ScopedObjectBase as ScopedObjectBaseType } from '../../redux/scopedObjects/types';

import { SCOPE_LEVELS, PAGE_TITLES } from '../config';
import { getIconsByScopeLevel, toLowerCaseAndPlural } from './utils';

import * as scopedObjectsActions from '../../redux/scopedObjects/action';
import * as scopeLevelsActions from '../../redux/scopeLevels/action';

import styles from './styles';

class ScopedObjectService extends PureComponent<IScopedObjectProps, IScopedObjectState> {
  parentIdRef: HTMLElement | null;

  isEditMode: boolean = false;

  scopeLevelObj: ScopeLevelType | null;

  icons: Icons;

  scopedObjectType: string;

  constructor(props: IScopedObjectProps, scopedObjectType: string) {
    super(props);

    this.checkIfEditMode();
    this.scopedObjectType = scopedObjectType;
    this.setPageTitle();
    this.icons = getIconsByScopeLevel(SCOPE_LEVELS[0]);
  }

  componentDidUpdate(prevProps: IScopedObjectProps, prevState: IScopedObjectState) {
    const { scopeLevel, isShowModal } = this.state;
    const { scopeLevel: scopeLevelPrev, isShowModal: isShowModalPrev } = prevState;
    const {
      history,
      scopedObjects,
      isScopedObjectCreating,
      isScopedObjectUpdating,
      match: { params: { scopedObjectId } },
    } = this.props;
    const {
      scopedObjects: scopedObjectsPrev,
      isScopedObjectCreating: isScopedObjectCreatingPrev,
      isScopedObjectUpdating: isScopedObjectUpdatingPrev,
    } = prevProps;

    const isScopeLevelChanged = scopeLevel !== scopeLevelPrev;
    const isCreatingStarted = !isScopedObjectCreatingPrev && isScopedObjectCreating;
    const isCreatingEnded = isScopedObjectCreatingPrev && !isScopedObjectCreating;
    const isUpdatingStarted = !isScopedObjectUpdatingPrev && isScopedObjectUpdating;
    const isUpdatingEnded = isScopedObjectUpdatingPrev && !isScopedObjectUpdating;
    const isScopedObjectCreated = scopedObjectsPrev.length < scopedObjects.length;
    const isModalClosed = isShowModalPrev && !isShowModal;
    const isScopedObjectsUpdated = scopedObjectsPrev !== scopedObjects;

    if (isCreatingStarted || isUpdatingStarted) {
      this.toggleDisableFields();
    }

    if (isModalClosed) {
      this.blurParentInput();
    }

    if (isCreatingEnded || isUpdatingEnded) {
      if (isScopedObjectCreated) {
        history.push(`/scopedObject/${this.scopedObjectType.toLowerCase()}`);
      } else {
        this.toggleDisableFields();
      }
    }

    if (isScopeLevelChanged) {
      this.icons = getIconsByScopeLevel(scopeLevel);
      this.handleParentRemove();
    }

    if (isScopedObjectsUpdated && scopedObjectId) {
      const scopedObject = scopedObjects.find(({ id }) => id === Number(scopedObjectId));

      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({ ...scopedObject });
    }
  }

  checkIfEditMode = (): void => {
    const {
      match: { params: { scopedObjectId } }, ACTION_SCOPED_OBJECT_DETAILS_REQUESTED,
    } = this.props;

    if (scopedObjectId) {
      ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(Number(scopedObjectId));

      this.isEditMode = true;
    }
  }

  setPageTitle = (): void => {
    const title = this.isEditMode ? PAGE_TITLES.EDIT_SO : PAGE_TITLES.ADD_SO;
    document.title = title(this.scopedObjectType);
  }

  blurParentInput = (): void => {
    this.parentIdRef.blur();
  }

  setParentRef = (ref: HTMLElement): void => {
    this.parentIdRef = ref;
  }

  handleInputChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    this.setState({ [name]: value });
  }

  toggleDisableFields = (): void => {
    this.setState(({ isFieldsDisabled }) => ({
      isFieldsDisabled: !isFieldsDisabled,
    }));
  }

  handleSubmitScopedObject = (): void => {
    const {
      isFieldsDisabled,
      isShowModal,
      ...scopedObjectData
    } = this.state;
    const {
      match: { params: { scopedObjectId } },
      ACTION_SCOPED_OBJECT_CREATE_REQUESTED,
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED,
    } = this.props;

    if (this.isEditMode) {
      ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(Number(scopedObjectId), scopedObjectData);
    } else if (this.scopeLevelObj) {
      const { id: parentId } = this.scopeLevelObj;
      Object.assign(scopedObjectData, { parentId });

      ACTION_SCOPED_OBJECT_CREATE_REQUESTED(scopedObjectData);
    }
  }

  showModal = (): void => {
    const { scopeLevel } = this.state;
    const { ACTION_SCOPE_LEVELS_REQUESTED } = this.props;

    ACTION_SCOPE_LEVELS_REQUESTED(scopeLevel.toLowerCase());

    this.setState({ isShowModal: true });
  }

  hideModal = (): void => {
    this.setState({ isShowModal: false });
  }

  handleLevelObjChoose = (level: ScopeLevelType): void => {
    this.scopeLevelObj = level;
    this.setState({ isShowModal: false });
  }

  handleParentRemove = (): void => {
    this.scopeLevelObj = null;
    this.forceUpdate();
  }
}

export const withSORedux = (
  Component: ReactElement<IScopedObjectProps>,
  scopedObjectType: string,
) => {
  const mapStateToProps = ({ scopedObjects, scopeLevels }) => ({
    scopedObjects: scopedObjects[toLowerCaseAndPlural(scopedObjectType)],
    isScopedObjectCreating: scopedObjects.isCreating,
    isScopedObjectUpdating: scopedObjects.isUpdating,
    scopeLevels,
  });

  const mapDispatchToProps = dispatch => ({
    ACTION_SCOPE_LEVELS_REQUESTED: (scopeLevel: string) => {
      dispatch(scopeLevelsActions.ACTION_SCOPE_LEVELS_REQUESTED(scopeLevel));
    },
    ACTION_SCOPED_OBJECT_DETAILS_REQUESTED: (scopedObjectId: number) => {
      dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_DETAILS_REQUESTED(
        scopedObjectId,
        toLowerCaseAndPlural(scopedObjectType),
      ));
    },
    ACTION_SCOPED_OBJECT_CREATE_REQUESTED: (scopedObjectData: ScopedObjectBaseType) => {
      dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_CREATE_REQUESTED(
        toLowerCaseAndPlural(scopedObjectType),
        scopedObjectData,
      ));
    },
    ACTION_SCOPED_OBJECT_UPDATE_REQUESTED: (
      scopedObjectId: number,
      scopedObjectData: ScopedObjectBaseType,
    ) => {
      dispatch(scopedObjectsActions.ACTION_SCOPED_OBJECT_UPDATE_REQUESTED(
        scopedObjectId,
        toLowerCaseAndPlural(scopedObjectType),
        scopedObjectData,
      ));
    },
  });

  return connect(
    mapStateToProps,
    mapDispatchToProps,
  )(
    withStyles(styles)(
      withRouter(Component),
    ),
  );
};

export default ScopedObjectService;
