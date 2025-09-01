
# 📌 Laravel Eloquent Relationships – Hoja Resumen
## 1️⃣ One To One (hasOne / belongsTo)
Si hay un *_id en una de las tablas → esa tabla usa belongsTo. 

La otra usa hasOne. 

1 registro en A ↔ 1 registro en B.

Ejemplo: users ↔ profiles

users.id → profiles.user_id

``` 
Profile.php     
    public function user() {
        return $this->belongsTo(User::class); 
    }
User.php
    public function profile() { 
        return $this->hasOne(Profile::class); 
    }
`````
`User 1 ─── 1 Profile`

## 2️⃣ One To Many (hasMany / belongsTo)
La tabla “hija” tiene un *_id apuntando a la “padre” → belongsTo.

La “padre” usa hasMany.

1 registro en A ↔ muchos en B.

Ejemplo: teams ↔ players

`teams.id → players.team_id`
````
Player.php
    public function team() {
        return $this->belongsTo(Team::class); 
    }

Team.php
    public function players() { 
        return $this->hasMany(Player::class); 
    }
````

`Team 1 ─── * Player`
## 3️⃣ Many To Many (belongsToMany)

No hay FK directa; hay tabla pivote con dos FKs.

Ambos lados usan belongsToMany.

Muchos ↔ muchos.

Ejemplo: players ↔ teams con player_team

`player_team: player_id, team_id`

````
Player.php
    public function teams() {
        return $this->belongsToMany(Team::class, 'player_team'); 
    }

Team.php
    public function players() {
        return $this->belongsToMany(Player::class, 'player_team');
     }
````

`Player * ─── * Team`

## 4️⃣ Has One Through
Saltar tabla intermedia para obtener un único registro.

“A tiene uno de C a través de B”.

Ejemplo: Country → User → Profile

````
public function profile(){
    return $this->hasOneThrough(Profile::class, User::class);
}
````
### Diagrama:
    Country 1 ─── 1 Profile (via User)

## 5️⃣ Has Many Through

Igual que hasOneThrough, pero el salto intermedio devuelve varios.

“A tiene muchos C a través de B”.

Ejemplo: Country → User → Post

````
public function posts(){
    return $this->hasManyThrough(Post::class, User::class);
}
````

### Diagrama:
    Country 1 ─── * Post (via User)

## 6️⃣ One To One (Polymorphic)

Igual que hasOne, pero con columnas *_id y *_type.

Un único registro puede pertenecer a distintos modelos.

Ejemplo: Image para User o Team

````
Image.php
    public function imageable() {
     return $this->morphTo(); 
    }

 User.php / Team.php
    public function image() {
        return $this->morphOne(Image::class, 'imageable'); 
    }
````
## Diagrama
    User 1 ──┐
             ├── 1 Image
    Team 1 ──┘

## 7️⃣ One To Many (Polymorphic)

Igual que hasMany pero con *_id y *_type.

Varios registros pueden pertenecer a distintos modelos.

Ejemplo: Comment para Post o Video
````
Comment.php
    public function commentable() { 
        return $this->morphTo(); 
    }

Post.php / Video.php
    public function comments() {
        return $this->morphMany(Comment::class, 'commentable'); 
    }
````
## Diagrama
    Post 1 ──┐
         ├── * Comment
    Video 1 ─┘

## 8️⃣ Many To Many (Polymorphic)

Como belongsToMany pero la tabla pivote tiene *_type.

Permite N a N entre múltiples modelos.

Ejemplo: Tag para Post o Video

````
 Tag.php
    public function posts() { 
        return $this->morphedByMany(Post::class, 'taggable'); 
    }
    public function videos() { 
        return $this->morphedByMany(Video::class, 'taggable'); 
    }

Post.php / Video.php
    public function tags() { 
        return $this->morphToMany(Tag::class, 'taggable'); 
    }
````
## Diagrama
    Post * ──┐
             ├── * Tag
    Video * ─┘

## Team  | Standing | Tournament

---

## Despliegue y permisos de servidor

Consulta `docs/server-permissions.md` para la configuración recomendada de permisos (ACLs, ownership y sudoers) entre el usuario del runtime (por ejemplo `www-data`) y el usuario de deploy.
````
┌───────────────────┐        ┌───────────────────┐
│      Team         │ 1     *│     Standing      │
│───────────────────│--------│───────────────────│
│ id (PK)           │        │ id (PK)           │
│ name              │        │ team_id (FK)      │
│ ...               │        │ tournament_id (FK)│
└───────────────────┘        │ matches_played    │
│ wins              │        └───────────────────┘
│ draws             │
│ losses            │
│ points            │
│ ...               │
└───────────────────┘
        *
        │
        │
        1
┌───────────────────┐
│   Tournament       │
│───────────────────│
│ id (PK)           │
│ name              │
│ league_id (FK)    │
│ ...               │
└───────────────────┘
