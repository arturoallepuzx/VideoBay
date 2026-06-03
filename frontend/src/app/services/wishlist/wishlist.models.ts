export interface MovieListItem {
  movie_id: string;
  tmdb_id: number | null;
  title: string | null;
  poster_path: string | null;
  release_year: number | null;
  added_at: string;
}

export interface MovieListPage {
  items: MovieListItem[];
  page: number;
  total_pages: number;
  total: number;
}

export interface FavoriteSlot {
  position: number;
  movie_id: string;
  title: string | null;
  poster_path: string | null;
  release_year: number | null;
}
