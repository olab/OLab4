import styled from 'styled-components';
import {
  DARK_BLUE, WHITE, BLUE, BLUE_GREY,
} from '../../shared/colors';

export const HeaderWrapper = styled.header`
  background-color: ${WHITE};
  min-height: 4vh;
  font-size: calc(10px + 2vmin);
  color: ${BLUE};
  .route-link {
    font-size: 1rem;
    color: ${BLUE};
    text-decoration: none;
  }

  > div:first-of-type {
    display: flex;
    align-items: center;
    padding: 5px 10px;
  }
`;

export const Logo = styled.div`
  text-decoration: none;
  display: flex;
  align-items: center;

  h1 {
    margin: 0;
    margin-left: 10px;
  }

  span {
    color: ${DARK_BLUE};
  }
`;

export const FakeProgress = styled.div`
  background-color: ${BLUE_GREY};
  height: 4px;
  width: 100%;
`;
