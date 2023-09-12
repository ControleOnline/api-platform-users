import * as types from './mutation_types';

export default {
  [types.SET_ISLOADING] (state, isLoading = true) {
    Object.assign(state, { isLoading: isLoading })
  }
}
