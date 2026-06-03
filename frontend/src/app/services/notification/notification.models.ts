export interface NotificationActor {
  uuid: string;
  name: string | null;
  avatar_url: string | null;
}

export interface NotificationMovie {
  uuid: string;
  title: string | null;
  poster_path: string | null;
  release_year: number | null;
}

export interface NotificationMetadata {
  actor?: NotificationActor;
  movie?: NotificationMovie;
  review?: { uuid: string; snippet: string | null };
  copy?: { uuid: string };
  order?: { uuid: string };
}

export interface AppNotification {
  uuid: string;
  type: string;
  title: string;
  body: string | null;
  action_url: string | null;
  metadata: NotificationMetadata | null;
  read_at: string | null;
  created_at: string;
}
