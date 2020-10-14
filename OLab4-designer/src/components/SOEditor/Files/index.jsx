// @flow
import React from 'react';
import { TextField, Chip, withStyles } from '@material-ui/core';

import SearchModal from '../../../shared/components/SearchModal';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import CopyToClipboard from '../../../shared/components/CopyToClipboard';

import convertSize from '../../../helpers/convertSize';
import getIconType from '../../../helpers/getIconType';
import ScopedObjectService, { withSORedux } from '../index.service';

import { EDITORS_FIELDS } from '../config';
import { TYPES, IMAGE_TYPES } from './config';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';

import type { IScopedObjectProps as IProps } from '../types';

import styles, { FieldLabel } from '../styles';
import {
  Title, Field, Outlined, ContainerTitle, Preview, FieldFileType,
} from './styles';

class File extends ScopedObjectService {
  constructor(props: IProps) {
    super(props, SCOPED_OBJECTS.FILE);
    this.state = {
      id: 0,
      type: 0,
      width: 0,
      height: 0,
      fileSize: 0,
      wiki: '',
      name: '',
      contents: '',
      copyright: '',
      originUrl: '',
      widthType: '',
      heightType: '',
      resourceUrl: '',
      description: '',
      isShowModal: false,
      isFieldsDisabled: false,
      scopeLevel: SCOPE_LEVELS[0],
    };
  }

  handleSelectChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const index = TYPES.findIndex(type => type === value);

    this.setState({ [name]: index });
  };

  render() {
    const {
      id, name, originUrl, scopeLevel, resourceUrl, widthType, copyright,
      description, isShowModal, isFieldsDisabled, heightType, fileSize,
      height, width, type, wiki,
    } = this.state;
    const { classes, scopeLevels } = this.props;
    const { iconEven: IconEven, iconOdd: IconOdd } = this.icons;
    const iconType = resourceUrl && resourceUrl.split('.').pop();
    const isPreviewShow = IMAGE_TYPES.includes(iconType);
    const MediaIconContent = getIconType(iconType);

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
      >
        {isPreviewShow && (
          <Preview src={resourceUrl} alt={name} />
        )}
        <Field>
          <Outlined>
            <OutlinedInput
              label={EDITORS_FIELDS.ID}
              value={id}
              fullWidth
              disabled
            />
          </Outlined>
          <CopyToClipboard text={id} medium />
        </Field>
        <Field>
          <Outlined>
            <OutlinedInput
              label={EDITORS_FIELDS.WIKI}
              value={wiki}
              fullWidth
              disabled
            />
          </Outlined>
          <CopyToClipboard text={wiki} medium />
        </Field>
        <Field>
          <Outlined>
            <OutlinedInput
              name="originUrl"
              label={EDITORS_FIELDS.ORIGIN_URL}
              value={originUrl}
              onChange={this.handleInputChange}
              fullWidth
            />
          </Outlined>
          <CopyToClipboard text={originUrl} medium />
        </Field>
        <Field>
          <Outlined>
            <ContainerTitle>
              <Title>
                {EDITORS_FIELDS.RESOURCE_URL}
                :
              </Title>
              <a
                href={resourceUrl}
                target="_blank"
                rel="noopener noreferrer"
              >
                {resourceUrl}
              </a>
            </ContainerTitle>
          </Outlined>
          <CopyToClipboard text={resourceUrl} medium />
        </Field>
        <Outlined>
          <OutlinedSelect
            name="type"
            label={EDITORS_FIELDS.TYPE}
            value={TYPES[type || 0]}
            values={TYPES}
            onChange={this.handleSelectChange}
            labelWidth={90}
            fullWidth
          />
        </Outlined>
        <Outlined>
          <OutlinedInput
            name="name"
            label={EDITORS_FIELDS.NAME}
            value={name}
            onChange={this.handleInputChange}
            fullWidth
          />
        </Outlined>
        <Outlined>
          <TextField
            multiline
            rows="3"
            name="description"
            label={EDITORS_FIELDS.DESCRIPTION}
            variant="outlined"
            value={description}
            onChange={this.handleInputChange}
            fullWidth
          />
        </Outlined>
        <Outlined>
          <OutlinedInput
            name="copyright"
            label={EDITORS_FIELDS.COPYRIGHT}
            value={copyright}
            onChange={this.handleInputChange}
            fullWidth
          />
        </Outlined>
        <FieldFileType>
          <Title>
            {EDITORS_FIELDS.FILE_TYPE}
            :
          </Title>
          <MediaIconContent />
        </FieldFileType>
        <Field>
          <ContainerTitle>
            <Title>
              {EDITORS_FIELDS.FILE_SIZE}
              :
            </Title>
            {convertSize(fileSize)}
          </ContainerTitle>
          <CopyToClipboard text={convertSize(fileSize)} medium />
        </Field>
        <Field>
          <ContainerTitle>
            <Title>
              {EDITORS_FIELDS.WIDTH}
              :
            </Title>
            {`${width}${widthType}`}
          </ContainerTitle>
          <CopyToClipboard text={`${width}${widthType}`} medium />
        </Field>
        <Field>
          <ContainerTitle>
            <Title>
              {EDITORS_FIELDS.HEIGHT}
              :
            </Title>
            {`${height}${heightType}`}
          </ContainerTitle>
          <CopyToClipboard text={`${height}${heightType}`} medium />
        </Field>
        {!this.isEditMode && (
          <>
            <Outlined>
              <OutlinedSelect
                name="scopeLevel"
                label={EDITORS_FIELDS.SCOPE_LEVEL}
                value={scopeLevel}
                values={SCOPE_LEVELS}
                onChange={this.handleInputChange}
                disabled={isFieldsDisabled}
                labelWidth={90}
                fullWidth
              />
            </Outlined>
            <FieldLabel>
              <Title>
                {EDITORS_FIELDS.PARENT}
              </Title>
              <Outlined>
                <OutlinedInput
                  name="parentId"
                  placeholder={this.scopeLevelObj ? '' : EDITORS_FIELDS.PARENT}
                  disabled={isFieldsDisabled}
                  onFocus={this.showModal}
                  setRef={this.setParentRef}
                  readOnly
                  fullWidth
                />
                {this.scopeLevelObj && (
                  <Chip
                    className={classes.chip}
                    label={this.scopeLevelObj.name}
                    variant="outlined"
                    color="primary"
                    onDelete={this.handleParentRemove}
                    icon={<IconEven />}
                  />
                )}
              </Outlined>
            </FieldLabel>
          </>
        )}
        {isShowModal && (
          <SearchModal
            label="Parent record"
            searchLabel="Search for parent record"
            items={scopeLevels[scopeLevel.toLowerCase()]}
            text={`Please choose appropriate parent from ${scopeLevel}:`}
            onClose={this.hideModal}
            onItemChoose={this.handleLevelObjChoose}
            isItemsFetching={scopeLevels.isFetching}
            iconEven={IconEven}
            iconOdd={IconOdd}
          />
        )}
      </EditorWrapper>
    );
  }
}

export default withSORedux(withStyles(styles)(File), SCOPED_OBJECTS.FILE);
