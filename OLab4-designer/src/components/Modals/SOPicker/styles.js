import styled, { css, keyframes } from 'styled-components';

import {
  GREY, GREEN, LIGHT_BLUE, DARK_GREY,
} from '../../../shared/colors';

const colorDark = 'rgba(36,68,106, 1)';
const colorDarkWithOpacity = 'rgba(36,68,106,.7)';
const colorDarkBorder = 'rgba(36,68,106,.16)';

export const ModalBody = styled.div`
  height: 260px;
  padding-left: 1rem;
  padding-right: ${({ isScrollbarVisible }) => (isScrollbarVisible ? 'calc(1rem - 5px)' : '1rem')};
  padding-bottom: 0;
  overflow: auto;
  ${({ isScrollbarVisible }) => (isScrollbarVisible && css`
    margin-right: 5px;

    &:hover {
      &::-webkit-scrollbar {
        width: 7px;
      }

      padding-right: calc(1rem - 12px);
    }
  `)};

  &::-webkit-scrollbar {
    width: 0;
    background-color: ${GREY};
  }
  &::-webkit-scrollbar-thumb {
    background-color: ${DARK_GREY};
    border-radius: 4px;
  }
  &::-webkit-scrollbar-track {
    margin: 5px;
  }
  &::-webkit-scrollbar-button {
    width: 0;
    height: 0;
    display: none;
  }
  &::-webkit-scrollbar-corner {
    background-color: transparent;
  }
`;

export const SOList = styled.ul`
  padding: 0;
  margin: 0;
  list-style-type: none;

  > li:first-child {
    border-top: none;
    padding-top: 0;
  }
`;

export const SOItem = styled.li`
  font-size: 13px;
  line-height: 16px;
  letter-spacing: 0.06em;
  color: ${colorDarkWithOpacity};
  border-top: 1px solid ${colorDarkBorder};
  padding: 3px 0;
`;

export const SOItemHeader = styled.div`
  display: flex;
  align-items: center;
`;

export const SOItemTitle = styled.p`
  font-family: SF Pro Display;
  font-size: 16px;
  line-height: 19px;
  margin: 0;
  color: ${colorDark};
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
`;

export const ModalFooter = styled.div`
  padding: .35rem 1rem;
  margin-top: auto;
  border-top: 2px solid ${LIGHT_BLUE};
  display: flex;
  align-items: center;
`;

export const SearchBox = styled.div`
  position: relative;
  margin-right: 1rem;

  > input {
    border-radius: 16px;
    background: ${LIGHT_BLUE};
    border: none;
    outline: none;
    height: 30px;
    padding-left: 32px;
    padding-right: 10px;
    width: 100%;
    box-sizing: border-box;
    border: 1px solid transparent;
    font-family: SF Pro Display;
    font-size: 14px;
    letter-spacing: 0.06em;
    color: ${DARK_GREY};

    &:focus {
      border-color: ${GREEN};
      box-shadow: 0 0 3px ${GREEN};
    }
  }
`;

export const SearchIconWrapper = styled.span`
  position: absolute;
  top: 50%;
  left: 10px;
  transform: translate(0, -45%);
`;

export const ConfigArticle = styled.article`
  display: flex;
  justify-content: space-between;
  padding: .5rem 1rem .3rem 1rem;
  border-bottom: 2px solid ${LIGHT_BLUE};

  > div:first-of-type {
    margin-right: .5rem;
  }

  > div:last-of-type {
    margin-left: .5rem;
  }
`;

export const EmptyList = styled.p`
  font-style: italic;
  position: absolute;
  color: black;
  margin: 0;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
`;

const rotateAnimation = keyframes`
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
`;

const keyframesMixin = css`
  animation: ${rotateAnimation} 2s linear infinite;
`;

export const ReloadIconWrapper = styled.span`
  ${({ isRotating }) => (isRotating ? keyframesMixin : 'animation: none;')};
  animation-fill-mode: forwards;
  transform-origin: center center;
  transform: rotate(360deg);
  display: inline-block;
  height: 18px;
`;
